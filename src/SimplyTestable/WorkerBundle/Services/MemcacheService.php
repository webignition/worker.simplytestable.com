<?php
namespace SimplyTestable\WorkerBundle\Services;

class MemcacheService
{
    /**
     * @var \Memcache
     */
    private $memcache;

    /**
     * @return \Memcache
     */
    public function get()
    {
        if (is_null($this->memcache)) {
            if (class_exists('\Memcache')) {
                $this->memcache = new \Memcache();
                $this->memcache->addserver('localhost');
            }
        }

        return $this->memcache;
    }
}
