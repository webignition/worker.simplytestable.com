<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\Guzzle\Middleware\RequestHeaders\RequestHeadersMiddleware;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class HttpHandlerStackFactory
{
    const MIDDLEWARE_CACHE_KEY = 'cache';
    const MIDDLEWARE_HTTP_AUTH_KEY = 'http-auth';
    const MIDDLEWARE_REQUEST_HEADERS_KEY = 'request-headers';
    const MIDDLEWARE_HISTORY_KEY = 'history';

    /**
     * @var HttpAuthenticationMiddleware
     */
    private $httpAuthenticationMiddleware;

    /**
     * @var RequestHeadersMiddleware
     */
    private $requestHeadersMiddleware;

    /**
     * @var HttpHistoryContainer
     */
    private $historyContainer;

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
     * @param HttpHistoryContainer $historyContainer
     * @param CacheMiddleware|null $cacheMiddleware
     * @param callable|null $handler
     */
    public function __construct(
        HttpAuthenticationMiddleware $httpAuthenticationMiddleware,
        RequestHeadersMiddleware $requestHeadersMiddleware,
        HttpHistoryContainer $historyContainer,
        CacheMiddleware $cacheMiddleware = null,
        callable $handler = null
    ) {
        $this->httpAuthenticationMiddleware = $httpAuthenticationMiddleware;
        $this->requestHeadersMiddleware = $requestHeadersMiddleware;
        $this->historyContainer = $historyContainer;
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
        $handlerStack->push(Middleware::history($this->historyContainer), self::MIDDLEWARE_HISTORY_KEY);

        return $handlerStack;
    }
}
