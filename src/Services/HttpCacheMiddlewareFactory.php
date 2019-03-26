<?php

namespace App\Services;

use Doctrine\Common\Cache\MemcachedCache;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;

class HttpCacheMiddlewareFactory
{
    private $cache;

    public function __construct(MemcachedCache $memcachedCache)
    {
        $this->cache = $memcachedCache;
    }

    public function create(): CacheMiddleware
    {
        return new CacheMiddleware(
            new PrivateCacheStrategy(
                new DoctrineCacheStorage(
                    $this->cache
                )
            )
        );
    }
}
