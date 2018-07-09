<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;

class HttpHandlerStackFactory
{
    const MIDDLEWARE_CACHE_KEY = 'cache';

    /**
     * @var CacheMiddleware
     */
    private $cacheMiddleware;

    /**
     * @var callable|null
     */
    private $handler;

    /**
     * @param CacheMiddleware|null $cacheMiddleware
     * @param callable|null $handler
     */
    public function __construct(
        CacheMiddleware $cacheMiddleware = null,
        callable $handler = null
    ) {
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

        return $handlerStack;
    }
}
