<?php

namespace SimplyTestable\WorkerBundle\Tests\Unit\Guzzle;

use Memcache;

class MemcacheServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testHasMemcache()
    {
        $this->assertTrue(class_exists(Memcache::class));
    }
}
