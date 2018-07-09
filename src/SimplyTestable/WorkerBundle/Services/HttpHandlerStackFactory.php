<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\Guzzle\Middleware\RequestHeaders\RequestHeadersMiddleware;

class HttpHandlerStackFactory
{
    const MIDDLEWARE_CACHE_KEY = 'cache';
    const MIDDLEWARE_HTTP_AUTH_KEY = 'http-auth';
    const MIDDLEWARE_REQUEST_HEADERS_KEY = 'request-headers';

    /**
     * @var HttpAuthenticationMiddleware
     */
    private $httpAuthenticationMiddleware;

    /**
     * @var RequestHeadersMiddleware
     */
    private $requestHeadersMiddleware;


    /**
     * @var CacheMiddleware
     */
    private $cacheMiddleware;

    /**
     * @var callable|null
     */
    private $handler;

    /**
     * @param HttpAuthenticationMiddleware $httpAuthenticationMiddleware
     * @param RequestHeadersMiddleware $requestHeadersMiddleware
     * @param CacheMiddleware|null $cacheMiddleware
     * @param callable|null $handler
     */
    public function __construct(
        HttpAuthenticationMiddleware $httpAuthenticationMiddleware,
        RequestHeadersMiddleware $requestHeadersMiddleware,
        CacheMiddleware $cacheMiddleware = null,
        callable $handler = null
    ) {
        $this->httpAuthenticationMiddleware = $httpAuthenticationMiddleware;
        $this->requestHeadersMiddleware = $requestHeadersMiddleware;
        $this->cacheMiddleware = $cacheMiddleware;
        $this->handler = $handler;
    }

    /**
     * @return HandlerStack
     */
    public function create()
    {
        $handlerStack = HandlerStack::create($this->handler);

        if ($this->cacheMiddleware) {
            $handlerStack->push($this->cacheMiddleware, self::MIDDLEWARE_CACHE_KEY);
        }

        $handlerStack->push($this->httpAuthenticationMiddleware, self::MIDDLEWARE_HTTP_AUTH_KEY);
        $handlerStack->push($this->requestHeadersMiddleware, self::MIDDLEWARE_REQUEST_HEADERS_KEY);

        return $handlerStack;
    }
}
