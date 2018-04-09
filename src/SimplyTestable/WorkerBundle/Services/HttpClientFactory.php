<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpClientFactory
{
    const MIDDLEWARE_CACHE_KEY = 'cache';
    const MIDDLEWARE_RETRY_KEY = 'retry';

    const MAX_RETRIES = 5;

    /**
     * @var array
     */
    private $curlOptions;

    /**
     * @var HttpCache
     */
    private $cache;

    /**
     * @param array $curlOptions
     * @param HttpCache $cache
     */
    public function __construct(array $curlOptions, HttpCache $cache)
    {
        $this->setCurlOptions($curlOptions);
        $this->cache = $cache;
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
    public function create()
    {
        $initialHandler = $this->createInitialHandler();

        if (empty($intialHandler)) {
            $handlerStack = HandlerStack::create();
        } else {
            $handlerStack = HandlerStack::create($intialHandler);
        }

        $cacheMiddleware = $this->createCacheMiddleware();
        if ($cacheMiddleware) {
            $handlerStack->push($cacheMiddleware, self::MIDDLEWARE_CACHE_KEY);
        }

        $handlerStack->push(Middleware::retry($this->createRetryDecider()), self::MIDDLEWARE_RETRY_KEY);

        return new HttpClient([
            'curl' => $this->curlOptions,
            'verify' => false,
            'handler' => $handlerStack,
            'max_retries' => 5,
            'cookies' => true,
        ]);
    }

    /**
     * @return callable|null
     */
    protected function createInitialHandler()
    {
        return [];
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
