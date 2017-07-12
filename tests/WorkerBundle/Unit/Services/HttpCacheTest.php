<?php

namespace Tests\WorkerBundle\Unit\Services;

use Doctrine\Common\Cache\MemcachedCache;
use Memcached;
use SimplyTestable\WorkerBundle\Services\HttpCache;
use SimplyTestable\WorkerBundle\Services\MemcachedService;

class HttpCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getDataProvider
     *
     * @param Memcached|null $memcachedServiceGetReturnValue
     * @param MemcachedCache|null $expectedReturnValue
     */
    public function testGet($memcachedServiceGetReturnValue, $expectedReturnValue)
    {
        /* @var MemcachedService $memcachedService */
        $memcachedService = \Mockery::mock(MemcachedService::class);
        $memcachedService
            ->shouldReceive('get')
            ->andReturn($memcachedServiceGetReturnValue);

        $httpCache = new HttpCache($memcachedService);
        $this->assertEquals($expectedReturnValue, $httpCache->get());
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        $memcached = new Memcached();
        $memcachedCache = new MemcachedCache();
        $memcachedCache->setMemcached($memcached);

        return [
            'failure' => [
                'memcachedServiceGetReturnValue' => null,
                'expectedReturnValue' => null,
            ],
            'success' => [
                'memcachedServiceGetReturnValue' => $memcached,
                'expectedReturnValue' => $memcachedCache,
            ],
        ];
    }

    /**
     * @dataProvider clearDataProvider
     *
     * @param $memcachedServiceGetReturnValue
     * @param $expectedReturnValue
     */
    public function testClear($memcachedServiceGetReturnValue, $expectedReturnValue)
    {
        /* @var MemcachedService $memcachedService */
        $memcachedService = \Mockery::mock(MemcachedService::class);
        $memcachedService
            ->shouldReceive('get')
            ->andReturn($memcachedServiceGetReturnValue);

        $httpCache = new HttpCache($memcachedService);
        $this->assertEquals($expectedReturnValue, $httpCache->clear());
    }

    /**
     * @return array
     */
    public function clearDataProvider()
    {
        return [
            'failure' => [
                'memcachedServiceGetReturnValue' => null,
                'expectedReturnValue' => false,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
