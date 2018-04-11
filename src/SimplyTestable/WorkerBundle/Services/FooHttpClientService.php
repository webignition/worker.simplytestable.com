<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SimplyTestable\WorkerBundle\Model\HttpAuthenticationCredentials;
use SimplyTestable\WorkerBundle\Model\HttpAuthenticationHeader;
use SimplyTestable\WorkerBundle\Services\GuzzleMiddleware\HttpAuthenticationMiddleware;
use SimplyTestable\WorkerBundle\Services\GuzzleMiddleware\RequestHeadersMiddleware;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class FooHttpClientService
{
    const MIDDLEWARE_CACHE_KEY = 'cache';
    const MIDDLEWARE_RETRY_KEY = 'retry';
    const MIDDLEWARE_HISTORY_KEY = 'history';
    const MIDDLEWARE_HTTP_AUTH_KEY = 'http-auth';
    const MIDDLEWARE_REQUEST_HEADERS_KEY = 'request-headers';

    const MAX_RETRIES = 5;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var array
     */
    private $curlOptions;

    /**
     * @var HttpCache
     */
    private $cache;

    /**
     * @var HttpHistoryContainer
     */
    private $historyContainer;

    /**
     * @var HttpAuthenticationMiddleware
     */
    private $httpAuthenticationMiddleware;

    /**
     * @var CookieJarInterface
     */
    private $cookieJar;

    /**
     * @var RequestHeadersMiddleware
     */
    private $requestHeadersMiddleware;

    /**
     * @param array $curlOptions
     * @param HttpCache $cache
     */
    public function __construct(array $curlOptions, HttpCache $cache)
    {
        $this->setCurlOptions($curlOptions);
        $this->cache = $cache;
        $this->historyContainer = new HttpHistoryContainer();
        $this->httpAuthenticationMiddleware = new HttpAuthenticationMiddleware();
        $this->cookieJar = new CookieJar();
        $this->requestHeadersMiddleware = new RequestHeadersMiddleware();

        $this->httpClient = $this->create();
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Set cookies to be sent on all requests (dependent on cookie domain/secure matching rules)
     *
     * @param array $cookies
     */
    public function setCookies($cookies = [])
    {
        $this->clearCookies();

        if (empty($cookies)) {
            return;
        }

        foreach ($cookies as $cookie) {
            foreach ($cookie as $key => $value) {
                $cookie[ucfirst($key)] = $value;
            }

            $this->cookieJar->setCookie(new SetCookie($cookie));
        }
    }

    public function clearCookies()
    {
        $this->cookieJar->clear();
    }

    /**
     * @return HttpHistoryContainer
     */
    public function getHistory()
    {
        return $this->historyContainer;
    }

    /**
     * @param HttpAuthenticationCredentials $httpAuthenticationCredentials
     */
    public function setBasicHttpAuthorization(HttpAuthenticationCredentials $httpAuthenticationCredentials)
    {
        $this->httpAuthenticationMiddleware->setHttpAuthenticationCredentials($httpAuthenticationCredentials);
    }

    public function clearBasicHttpAuthorization()
    {
        $this->httpAuthenticationMiddleware->setHttpAuthenticationCredentials(new HttpAuthenticationCredentials());
    }

    public function setRequestHeader($name, $value)
    {
        $this->requestHeadersMiddleware->setHeader($name, $value);
    }

    /**
     * @param array $curlOptions
     */
    private function setCurlOptions(array $curlOptions)
    {
        $definedCurlOptions = [];

        foreach ($curlOptions as $name => $value) {
            if (defined($name)) {
                $definedCurlOptions[constant($name)] = $value;
            }
        }

        $this->curlOptions = $definedCurlOptions;
    }

    /**
     * @return HttpClient
     */
    private function create()
    {
        $initialHandler = $this->createInitialHandler();

        if (is_null($initialHandler)) {
            $handlerStack = HandlerStack::create();
        } else {
            $handlerStack = HandlerStack::create($initialHandler);
        }

        $cacheMiddleware = $this->createCacheMiddleware();
        if ($cacheMiddleware) {
            $handlerStack->push($cacheMiddleware, self::MIDDLEWARE_CACHE_KEY);
        }

        $handlerStack->push($this->httpAuthenticationMiddleware, self::MIDDLEWARE_HTTP_AUTH_KEY);
        $handlerStack->push($this->requestHeadersMiddleware, self::MIDDLEWARE_REQUEST_HEADERS_KEY);
        $handlerStack->push(Middleware::retry($this->createRetryDecider()), self::MIDDLEWARE_RETRY_KEY);
        $handlerStack->push(Middleware::history($this->historyContainer), self::MIDDLEWARE_HISTORY_KEY);

        return new HttpClient([
            'curl' => $this->curlOptions,
            'verify' => false,
            'handler' => $handlerStack,
            'max_retries' => self::MAX_RETRIES,
            'cookies' => $this->cookieJar,
        ]);
    }

    /**
     * @return callable|null
     */
    protected function createInitialHandler()
    {
        return null;
    }

    /**
     * @return CacheMiddleware|null
     */
    private function createCacheMiddleware()
    {
        if (!$this->cache->has()) {
            return null;
        }

        return new CacheMiddleware(
            new PrivateCacheStrategy(
                new DoctrineCacheStorage(
                    $this->cache->get()
                )
            )
        );
    }

    /**
     * @return \Closure
     */
    private function createRetryDecider()
    {
        return function (
            $retries,
            RequestInterface $request,
            ResponseInterface $response = null,
            GuzzleException $exception = null
        ) {
            if ($retries >= self::MAX_RETRIES) {
                return false;
            }

            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response instanceof ResponseInterface && $response->getStatusCode() >= 500) {
                return true;
            }

            return false;
        };
    }
}
