<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Subscriber\Cache\CacheSubscriber;
use GuzzleHttp\Subscriber\Cache\CacheStorage;
use GuzzleHttp\Subscriber\Cookie as HttpCookieSubscriber;
use GuzzleHttp\Subscriber\Retry\RetrySubscriber as HttpRetrySubscriber;
use GuzzleHttp\Subscriber\History as HttpHistorySubscriber;

class HttpClientService
{
    /**
     * @var HttpClient
     */
    protected $httpClient = null;

    /**
     * @var HttpCache
     */
    private $httpCache = null;

    /**
     * @var array
     */
    private $curlOptions = array();

    /**
     * @var HttpHistorySubscriber
     */
    private $historySubscriber;

    /**
     * @var HttpCookieSubscriber
     */
    private $cookieSubscriber;

    /**
     * @var HttpRetrySubscriber
     */
    private $retrySubscriber;

    /**
     * @param HttpClient $httpClient
     * @param HttpCache $httpCache
     */
    public function __construct(
        HttpClient $httpClient,
        HttpCache $httpCache
    ) {
        $this->httpCache = $httpCache;

        $this->historySubscriber = new HttpHistorySubscriber();
        $this->cookieSubscriber = new HttpCookieSubscriber();
        $this->retrySubscriber = $this->createRetrySubscriber();

//        $this->httpClient = new HttpClient([
//            'config' => [
//                'curl' => $this->curlOptions
//            ],
//            'defaults' => [
//                'verify' => false,
//            ],
//        ]);

        $cacheSubscriber = $this->createCacheSubscriber();
        if (!is_null($cacheSubscriber)) {
            $this->httpClient->getEmitter()->attach($this->createCacheSubscriber());
        }

        $this->enableRetrySubscriber();
        $this->httpClient->getEmitter()->attach($this->historySubscriber);
        $this->httpClient->getEmitter()->attach($this->cookieSubscriber);
    }

    public function enableRetrySubscriber()
    {
        $this->httpClient->getEmitter()->attach($this->retrySubscriber);
    }

    public function disableRetrySubscriber()
    {
        $this->httpClient->getEmitter()->detach($this->retrySubscriber);
    }

    /**
     * @return HttpClient
     */
    public function get()
    {
        return $this->httpClient;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $defaultHeaders = $this->get()->getDefaultOption('headers');
        $defaultHeaders['User-Agent'] = $userAgent;

        $this->get()->setDefaultOption('headers', $defaultHeaders);
    }

    public function resetUserAgent()
    {
        $client = $this->get();
        $this->setUserAgent($client::getDefaultUserAgent());
    }

    /**
     * @return HttpRetrySubscriber
     */
    protected function createRetrySubscriber()
    {
        $filter = HttpRetrySubscriber::createChainFilter([
            // Does early filter to force non-idempotent methods to NOT be retried.
            HttpRetrySubscriber::createIdempotentFilter(),
            // Retry curl-level errors
            HttpRetrySubscriber::createCurlFilter(),
            // Performs the last check, returning ``true`` or ``false`` based on
            // if the response received a 500 or 503 status code.
            HttpRetrySubscriber::createStatusFilter([500, 503])
        ]);

        return new HttpRetrySubscriber(['filter' => $filter]);
    }

    /**
     * @return CacheSubscriber|null
     */
    private function createCacheSubscriber()
    {
        if (!$this->httpCache->has()) {
            return null;
        }

        $cacheSubscriber = new CacheSubscriber(
            new CacheStorage($this->httpCache->get()),
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
     * @return HttpHistorySubscriber
     */
    public function getHistory()
    {
        return $this->historySubscriber;
    }

    /**
     * Set cookies to be sent on all requests (dependent on cookie domain/secure matching rules)
     *
     * @param array $cookies
     */
    public function setCookies($cookies = [])
    {
        $this->cookieSubscriber->getCookieJar()->clear();
        if (!empty($cookies)) {
            foreach ($cookies as $cookie) {
                foreach ($cookie as $key => $value) {
                    $cookie[ucfirst($key)] = $value;
                }

                $this->cookieSubscriber->getCookieJar()->setCookie(new SetCookie($cookie));
            }
        }
    }

    public function clearCookies()
    {
        $this->cookieSubscriber->getCookieJar()->clear();
    }

    public function setBasicHttpAuthorization($username, $password)
    {
        if (empty($username) && empty($password)) {
            return;
        }

        $this->get()->setDefaultOption(
            'auth',
            [$username, $password]
        );
    }

    public function clearBasicHttpAuthorization()
    {
        $this->get()->setDefaultOption(
            'auth',
            null
        );
    }
}
