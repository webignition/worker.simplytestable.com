<?php

namespace AppBundle\Services;

use Doctrine\Common\Cache\MemcachedCache;

class HttpCache
{
    /**
     * @var MemcachedCache
     */
    private $memcachedCache;

    /**
     * @var MemcachedService
     */
    private $memcachedService;

    /**
     * @param MemcachedService $memcachedService
     */
    public function __construct(MemcachedService $memcachedService)
    {
        $this->memcachedService = $memcachedService;
    }

    /**
     * @return MemcachedCache
     */
    public function get()
    {
        if (is_null($this->memcachedCache)) {
            $memcached = $this->memcachedService->get();
            if (!is_null($memcached)) {
                $this->memcachedCache = new MemcachedCache();
                $this->memcachedCache->setMemcached($memcached);
            }
        }

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
