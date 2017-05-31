<?php
namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Subscriber\Retry\RetrySubscriber;
use GuzzleHttp\Subscriber\Cache\CacheSubscriber;
use GuzzleHttp\Subscriber\Cache\CacheStorage;
use Doctrine\Common\Cache\MemcacheCache;
use GuzzleHttp\Subscriber\History as HttpHistorySubscriber;
use GuzzleHttp\Event\SubscriberInterface as HttpSubscriberInterface;
use SimplyTestable\WorkerBundle\Services\MemcacheService;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Message\Request as HttpRequest;

class HttpClientService {


    /**
     *
     * @var HttpClient
     */
    protected $httpClient = null;


    /**
     *
     * @var (\SimplyTestable\WorkerBundle\Services\MemcacheService
     */
    private $memcacheService = null;


    /**
     *
     * @var \Doctrine\Common\Cache\MemcacheCache
     */
    private $memcacheCache = null;


    /**
     *
     * @var array
     */
    private $curlOptions = array();


    /**
     *
     * @param MemcacheService $memcacheService
     * @param array $curlOptions
     */
    public function __construct(
            MemcacheService $memcacheService,
            $curlOptions
    ) {
        $this->memcacheService = $memcacheService;

        foreach ($curlOptions as $curlOption) {
            if (defined($curlOption['name'])) {
                $this->curlOptions[constant($curlOption['name'])] = $curlOption['value'];
            }
        }
    }


    /**
     * @param array $defaultRequestOptions
     * @return HttpClient
     */
    public function get($defaultRequestOptions = []) {
        $defaultRequestOptions = $this->buildHttpClientOptions($defaultRequestOptions);

        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient($defaultRequestOptions);
            $this->enableSubscribers();
        }

        if (!empty($defaultRequestOptions)) {
            foreach ($defaultRequestOptions as $key => $value) {
                $this->httpClient->setDefaultOption($key, $value);
            }
        }

        return $this->httpClient;
    }


    private function buildHttpClientOptions($defaultRequestOptions = []) {
        if (!isset($defaultRequestOptions['config'])) {
            $defaultRequestOptions['config'] = [];
        }

        if (!isset($defaultRequestOptions['config']['curl'])) {
            $defaultRequestOptions['config']['curl'] = [];
        }

        foreach ($this->curlOptions as $key => $value) {
            $defaultRequestOptions['config']['curl'][$key] = $value;
        }

        return $defaultRequestOptions;
    }


    public function setUserAgent($userAgent) {
        $defaultHeaders = $this->get()->getDefaultOption('headers');
        $defaultHeaders['User-Agent'] = $userAgent;

        $this->get()->setDefaultOption('headers', $defaultHeaders);
    }


    public function resetUserAgent() {
        $client = $this->get();
        $this->setUserAgent($client::getDefaultUserAgent());
    }


    public function enableSubscribers() {
        foreach ($this->getSubscribers() as $subscriber) {
            if (!$this->hasSubscriber(get_class($subscriber))) {
                $this->httpClient->getEmitter()->attach($subscriber);
            }
        }
    }


    public function removeSubscriber($subscriberClassName) {
        if (!$this->hasSubscriber($subscriberClassName)) {
            return true;
        }

        $this->get()->getEventDispatcher()->removeSubscriber($this->getPluginListener($subscriberClassName));
    }


    /**
     * @param $subscriberClassName
     * @return SubscriberInterface|null
     */
    private function getSubscriber($subscriberClassName) {
        if (is_null($this->httpClient)) {
            return null;
        }

        foreach ($this->httpClient->getEmitter()->listeners() as $eventName => $eventListeners) {
            foreach ($eventListeners as $eventListener) {
                if (get_class($eventListener[0]) == $subscriberClassName) {
                    return $eventListener[0];
                }
            }
        }

        return null;
    }


    /**
     *
     * @param string $subscriberClassName
     * @return boolean
     */
    public function hasSubscriber($subscriberClassName) {
        return !is_null($this->getSubscriber($subscriberClassName));
    }


    /**
     * @return HttpSubscriberInterface[]
     */
    protected function getSubscribers() {
        return [
            $this->getRetrySubscriber(),
            $this->getCacheSubscriber(),
            new HttpHistorySubscriber()
        ];
    }


    /**
     * @return RetrySubscriber
     */
    protected function getRetrySubscriber() {
        $filter = RetrySubscriber::createChainFilter([
            // Does early filter to force non-idempotent methods to NOT be retried.
            RetrySubscriber::createIdempotentFilter(),
            // Retry curl-level errors
            RetrySubscriber::createCurlFilter(),
            // Performs the last check, returning ``true`` or ``false`` based on
            // if the response received a 500 or 503 status code.
            RetrySubscriber::createStatusFilter([500, 503])
        ]);

        return new RetrySubscriber(['filter' => $filter]);
    }


    /**
     * @return CacheSubscriber|null
     */
    protected function getCacheSubscriber() {
        $memcacheCache = $this->getMemcacheCache();
        if (is_null($memcacheCache)) {
            return null;
        }

        $cacheSubscriber = new CacheSubscriber(
            new CacheStorage($memcacheCache),
            [
                'GuzzleHttp\Subscriber\Cache\Utils',
                'canCacheRequest'
            ]
        );

        return $cacheSubscriber;
    }

    /**
     * @param string $url
     * @param array $options
     *
     * @return RequestInterface
     */
    public function getRequest($url, array $options = [])
    {
        return $this->createRequest('GET', $url, $options);
    }


    /**
     * @param string $url
     * @param array $options
     *
     * @return RequestInterface
     */
    public function postRequest($url, array $options = [])
    {
        return $this->createRequest('POST', $url, $options);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $options
     *
     * @return RequestInterface
     */
    private function createRequest($method, $url, $options)
    {
        $options['config'] = [
            'curl' => $this->curlOptions
        ];

        return $this->get()->createRequest(
            $method,
            $url,
            $options
        );
    }

    /**
     *
     * @return MemcacheCache
     */
    public function getMemcacheCache() {
        if (is_null($this->memcacheCache)) {
            $memcache = $this->memcacheService->get();
            if (!is_null($memcache)) {
                $this->memcacheCache = new MemcacheCache();
                $this->memcacheCache->setMemcache($memcache);
            }
        }

        return $this->memcacheCache;
    }


    /**
     *
     * @return boolean
     */
    public function hasMemcacheCache() {
        return !is_null($this->getMemcacheCache());
    }



    /**
     * @return HttpHistorySubscriber
     */
    public function getHistory()
    {
        $subscriberCollections = $this->get()->getEmitter()->listeners('complete');

        foreach ($subscriberCollections as $subcriberCollection) {
            if ($subcriberCollection[0] instanceof HttpHistorySubscriber) {
                return $subcriberCollection[0];
            }
        }

        return null;
    }


    /**
     *
     * @return HttpMockSubscriber
     */
    public function getMockSubscriber() {
        if (!$this->hasSubscriber('GuzzleHttp\Subscriber\Mock')) {
            $this->get()->getEmitter()->attach(new HttpMockSubscriber());
        }

        return $this->getSubscriber('GuzzleHttp\Subscriber\Mock');
    }

}