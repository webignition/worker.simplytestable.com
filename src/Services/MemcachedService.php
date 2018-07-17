<?php
namespace App\Services;

use Memcached;

class MemcachedService
{
    const HOST = 'localhost';
    const PORT = 11211;

    /**
     * @var Memcached
     */
    private $memcached;

    /**
     * @return Memcached
     */
    public function get()
    {
        if (is_null($this->memcached)) {
            if (class_exists('\Memcached')) {
                $this->memcached = new Memcached();
                $this->memcached->addserver(self::HOST, self::PORT);
            }
        }

        return $this->memcached;
    }
}
