<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\Guzzle\Middleware\RequestHeaders\RequestHeadersMiddleware;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class HttpClientService
{
    const MIDDLEWARE_CACHE_KEY = 'cache';
    const MIDDLEWARE_RETRY_KEY = 'retry';
    const MIDDLEWARE_HISTORY_KEY = 'history';
    const MIDDLEWARE_HTTP_AUTH_KEY = 'http-auth';
    const MIDDLEWARE_REQUEST_HEADERS_KEY = 'request-headers';

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var array
     */
    private $curlOptions;

    /**
     * @var CacheMiddleware
     */
    private $cacheMiddleware;

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
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * @var HttpRetryMiddleware
     */
    private $httpRetryMiddleware;

    /**
     * @param array $curlOptions
     * @param HandlerStack $handlerStack
     * @param HttpHistoryContainer $historyContainer
     * @param HttpRetryMiddleware $httpRetryMiddleware
     * @param CacheMiddleware|null $cacheMiddleware
     */
    public function __construct(
        array $curlOptions,
        HandlerStack $handlerStack,
        HttpHistoryContainer $historyContainer,
        HttpRetryMiddleware $httpRetryMiddleware,
        CacheMiddleware $cacheMiddleware = null
    ) {
        $this->setCurlOptions($curlOptions);

        $this->historyContainer = $historyContainer;
        $this->httpRetryMiddleware = $httpRetryMiddleware;
        $this->cacheMiddleware = $cacheMiddleware;
        $this->httpAuthenticationMiddleware = new HttpAuthenticationMiddleware();
        $this->cookieJar = new CookieJar();
        $this->requestHeadersMiddleware = new RequestHeadersMiddleware();
        $this->handlerStack = $handlerStack;

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
     * @param SetCookie[] $cookies
     */
    public function setCookies(array $cookies = [])
    {
        $this->clearCookies();

        if (empty($cookies)) {
            return;
        }

        foreach ($cookies as $cookie) {
            $this->cookieJar->setCookie($cookie);
        }
    }

    public function clearCookies()
    {
        $this->cookieJar->clear();
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

    /**
     * @param string $name
     * @param mixed $value
     */
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
        if ($this->cacheMiddleware) {
            $this->handlerStack->push($this->cacheMiddleware, self::MIDDLEWARE_CACHE_KEY);
        }

        $this->handlerStack->push($this->httpAuthenticationMiddleware, self::MIDDLEWARE_HTTP_AUTH_KEY);
        $this->handlerStack->push($this->requestHeadersMiddleware, self::MIDDLEWARE_REQUEST_HEADERS_KEY);
        $this->handlerStack->push(Middleware::history($this->historyContainer), self::MIDDLEWARE_HISTORY_KEY);
        $this->httpRetryMiddleware->enable();

        return new HttpClient([
            'curl' => $this->curlOptions,
            'verify' => false,
            'handler' => $this->handlerStack,
            'max_retries' => HttpRetryMiddlewareFactory::MAX_RETRIES,
            'cookies' => $this->cookieJar,
        ]);
    }
}
