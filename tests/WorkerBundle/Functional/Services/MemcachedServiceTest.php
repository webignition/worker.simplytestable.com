<?php

namespace Tests\WorkerBundle\Functional\Guzzle;

use Memcached;
use SimplyTestable\WorkerBundle\Services\MemcachedService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class MemcachedServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var MemcachedService
     */
    private $memcachedService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->memcachedService = $this->container->get(MemcachedService::class);
    }

    public function testGetMemcachedService()
    {
        $this->assertTrue($this->memcachedService instanceof MemcachedService);
    }

    public function testGetMemcached()
    {
        $this->assertTrue($this->memcachedService->get() instanceof Memcached);
    }
}
