<?php

namespace App\Tests\Functional\Services;

use Memcached;
use App\Services\MemcachedService;
use App\Tests\Functional\AbstractBaseTestCase;

class MemcachedServiceTest extends AbstractBaseTestCase
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
        $this->memcachedService = self::$container->get(MemcachedService::class);
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
