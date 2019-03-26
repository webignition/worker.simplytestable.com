<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Services;

use App\Services\HttpCache;
use App\Services\MemcachedService;
use Mockery\MockInterface;

class HttpCacheTest extends \PHPUnit\Framework\TestCase
{
    public function testClear()
    {
        $this->markTestSkipped('To be removed');

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
