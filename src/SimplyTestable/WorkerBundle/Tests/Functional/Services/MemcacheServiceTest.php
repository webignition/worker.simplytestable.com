<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Guzzle;

use Memcache;
use Simplytestable\WorkerBundle\Services\MemcacheService;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;

class MemcacheServiceTest extends BaseSimplyTestableTestCase
{
    public function testGetMemcacheService()
    {
        $this->assertTrue($this->getMemcacheService() instanceof MemcacheService);
    }

    public function testGetMemcache()
    {
        $this->assertTrue($this->getMemcacheService()->get() instanceof Memcache);
    }
}

