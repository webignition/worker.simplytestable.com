<?php

namespace SimplyTestable\WorkerBundle\Services;

use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;

class HttpCacheMiddlewareFactory
{
    /**
     * @var HttpCache
     */
    private $cache;

    /**
     * @param HttpCache $cache
     */
    public function __construct(HttpCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return CacheMiddleware|null
     */
    public function create()
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
}
