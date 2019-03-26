<?php

namespace App\Services;

use Doctrine\Common\Cache\MemcachedCache;

class HttpCache
{
    private $memcachedCache;

    public function __construct(MemcachedCache $memcachedCache)
    {
        $this->memcachedCache = $memcachedCache;
    }

    /**
     * @return MemcachedCache
     */
    public function get()
    {
        return $this->memcachedCache;
    }

    /**
     * @return bool
     */
    public function has()
    {
        return !is_null($this->get());
    }

    /**
     * @return bool
     */
    public function clear()
    {
        if (!$this->has()) {
            return false;
        }

        return $this->get()->deleteAll();
    }
}
