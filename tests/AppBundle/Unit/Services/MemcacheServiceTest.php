<?php

namespace Tests\AppBundle\Unit\Services;

use Memcached;

class MemcacheServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testHasMemcached()
    {
        $this->assertTrue(class_exists(Memcached::class));
    }
}