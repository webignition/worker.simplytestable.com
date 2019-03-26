<?php

namespace App\Services;

use Doctrine\Common\Cache\MemcachedCache;
use Memcached;

class HttpCache
{
    /**
     * @var MemcachedCache
     */
    private $memcachedCache;

    public function __construct(Memcached $memcached)
    {
        $this->memcachedCache = new MemcachedCache();
        $this->memcachedCache->setMemcached($memcached);
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
