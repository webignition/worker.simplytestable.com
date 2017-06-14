<?php

namespace SimplyTestable\WorkerBundle\Tests\Guzzle;

use Memcache;
use Simplytestable\WorkerBundle\Services\MemcacheService;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class MemcacheServiceTest extends BaseSimplyTestableTestCase
{
    public function testHasMemcache()
    {
        $this->assertTrue(class_exists(Memcache::class));
    }

    public function testGetMemcacheService()
    {
        $this->assertTrue($this->getMemcacheService() instanceof MemcacheService);
    }

    public function testGetMemcache()
    {
        $this->assertTrue($this->getMemcacheService()->get() instanceof Memcache);
    }
}

