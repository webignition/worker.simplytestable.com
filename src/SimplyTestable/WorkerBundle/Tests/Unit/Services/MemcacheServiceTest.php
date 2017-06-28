<?php

namespace SimplyTestable\WorkerBundle\Tests\Unit\Services;

use Memcached;

class MemcacheServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testHasMemcached()
    {
        $this->assertTrue(class_exists(Memcached::class));
    }
}
