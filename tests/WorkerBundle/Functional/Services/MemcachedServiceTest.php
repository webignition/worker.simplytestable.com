<?php

namespace Tests\WorkerBundle\Functional\Guzzle;

use Memcached;
use Simplytestable\WorkerBundle\Services\MemcachedService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class MemcachedServiceTest extends BaseSimplyTestableTestCase
{
    public function testGetMemcachedService()
    {
        $this->assertTrue($this->getMemcachedService() instanceof MemcachedService);
    }

    public function testGetMemcached()
    {
        $this->assertTrue($this->getMemcachedService()->get() instanceof Memcached);
    }
}
