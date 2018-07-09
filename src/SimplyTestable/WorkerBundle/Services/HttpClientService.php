<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\HandlerStack;

class HttpClientService
{
    const MIDDLEWARE_CACHE_KEY = 'cache';
    const MIDDLEWARE_RETRY_KEY = 'retry';
    const MIDDLEWARE_HISTORY_KEY = 'history';
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
     * @var CookieJarInterface
     */
    private $cookieJar;

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
     * @param CookieJarInterface $cookieJar
     * @param HttpRetryMiddleware $httpRetryMiddleware
     */
    public function __construct(
        array $curlOptions,
        HandlerStack $handlerStack,
        CookieJarInterface $cookieJar,
        HttpRetryMiddleware $httpRetryMiddleware
    ) {
        $this->setCurlOptions($curlOptions);

        $this->httpRetryMiddleware = $httpRetryMiddleware;
        $this->cookieJar = $cookieJar;
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
