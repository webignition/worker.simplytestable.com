<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Services;

use Doctrine\Common\Cache\MemcachedCache;
use Memcached;
use App\Services\HttpCache;
use App\Services\MemcachedService;
use Mockery\MockInterface;

class HttpCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getDataProvider
     */
    public function testGet(?Memcached $memcachedServiceGetReturnValue, ?MemcachedCache $expectedReturnValue)
    {
        /* @var MemcachedService|MockInterface $memcachedService */
        $memcachedService = \Mockery::mock(MemcachedService::class);
        $memcachedService
            ->shouldReceive('get')
            ->andReturn($memcachedServiceGetReturnValue);

        $httpCache = new HttpCache($memcachedService);
        $this->assertEquals($expectedReturnValue, $httpCache->get());
    }

    public function getDataProvider(): array
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

    public function testClear()
    {
        /* @var MemcachedService|MockInterface $memcachedService */
        $memcachedService = \Mockery::mock(MemcachedService::class);
        $memcachedService
            ->shouldReceive('get')
            ->andReturn(null);

        $httpCache = new HttpCache($memcachedService);
        $this->assertEquals(false, $httpCache->clear());
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
