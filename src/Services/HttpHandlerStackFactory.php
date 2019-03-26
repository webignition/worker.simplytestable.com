<?php

namespace App\Services;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\Guzzle\Middleware\RequestHeaders\RequestHeadersMiddleware;
use webignition\Guzzle\Middleware\ResponseLocationUriFixer\Factory as ResponseUriFixerFactory;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class HttpHandlerStackFactory
{
    const MIDDLEWARE_CACHE_KEY = 'cache';
    const MIDDLEWARE_HTTP_AUTH_KEY = 'http-auth';
    const MIDDLEWARE_REQUEST_HEADERS_KEY = 'request-headers';
    const MIDDLEWARE_HISTORY_KEY = 'history';
    const MIDDLEWARE_RESPONSE_URL_FIXER_KEY = 'response-url-fixer';

    private $httpAuthenticationMiddleware;
    private $requestHeadersMiddleware;
    private $historyContainer;
    private $responseUriFixerFactory;
    private $cacheMiddleware;
    private $handler;

    public function __construct(
        HttpAuthenticationMiddleware $httpAuthenticationMiddleware,
        RequestHeadersMiddleware $requestHeadersMiddleware,
        HttpHistoryContainer $historyContainer,
        ResponseUriFixerFactory $responseUriFixerFactory,
        CacheMiddleware $cacheMiddleware,
        callable $handler = null
    ) {
        $this->httpAuthenticationMiddleware = $httpAuthenticationMiddleware;
        $this->requestHeadersMiddleware = $requestHeadersMiddleware;
        $this->historyContainer = $historyContainer;
        $this->responseUriFixerFactory = $responseUriFixerFactory;
        $this->cacheMiddleware = $cacheMiddleware;
        $this->handler = $handler;
    }

    public function create(): HandlerStack
    {
        $handlerStack = HandlerStack::create($this->handler);

        if ($this->cacheMiddleware) {
            $handlerStack->push($this->cacheMiddleware, self::MIDDLEWARE_CACHE_KEY);
        }

        $handlerStack->push($this->responseUriFixerFactory->create(), self::MIDDLEWARE_RESPONSE_URL_FIXER_KEY);
        $handlerStack->push($this->httpAuthenticationMiddleware, self::MIDDLEWARE_HTTP_AUTH_KEY);
        $handlerStack->push($this->requestHeadersMiddleware, self::MIDDLEWARE_REQUEST_HEADERS_KEY);
        $handlerStack->push(Middleware::history($this->historyContainer), self::MIDDLEWARE_HISTORY_KEY);

        return $handlerStack;
    }
}
