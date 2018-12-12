<?php

namespace App\Tests\Unit\Services;

use App\Entity\CachedResource;
use App\Model\Source;
use App\Services\SourceFactory;

class SourceFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SourceFactory
     */
    private $sourceFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->sourceFactory = new SourceFactory();
    }

    public function testFromCachedResource()
    {
        $url = 'http://example.com/';
        $id = 'cached-resource-id';

        $cachedResource = \Mockery::mock(CachedResource::class);
        $cachedResource
            ->shouldReceive('getUrl')
            ->atLeast()
            ->once()
            ->andReturn($url);

        $cachedResource
            ->shouldReceive('getId')
            ->atLeast()
            ->once()
            ->andReturn($id);

        $source = $this->sourceFactory->fromCachedResource($cachedResource);

        $this->assertInstanceOf(Source::class, $source);
        $this->assertEquals($url, $source->getUrl());
        $this->assertEquals(Source::TYPE_CACHED_RESOURCE, $source->getType());
        $this->assertEquals($id, $source->getValue());
        $this->assertNull($source->getFailureType());
        $this->assertNull($source->getFailureCode());
    }

    public function testCreateHttpFailedSource()
    {
        $url = 'http://example.com/';
        $statusCode = 404;

        $source = $this->sourceFactory->createHttpFailedSource($url, $statusCode);

        $this->assertInstanceOf(Source::class, $source);
        $this->assertEquals($url, $source->getUrl());
        $this->assertEquals(Source::TYPE_UNAVAILABLE, $source->getType());
        $this->assertEquals('http:404', $source->getValue());
        $this->assertEquals(Source::FAILURE_TYPE_HTTP, $source->getFailureType());
        $this->assertEquals($statusCode, $source->getFailureCode());
    }

    public function testCreateCurlFailedSource()
    {
        $url = 'http://example.com/';
        $curlCode = 28;

        $source = $this->sourceFactory->createCurlFailedSource($url, $curlCode);

        $this->assertInstanceOf(Source::class, $source);
        $this->assertEquals($url, $source->getUrl());
        $this->assertEquals(Source::TYPE_UNAVAILABLE, $source->getType());
        $this->assertEquals('curl:28', $source->getValue());
        $this->assertEquals(Source::FAILURE_TYPE_CURL, $source->getFailureType());
        $this->assertEquals($curlCode, $source->getFailureCode());
    }

    public function testCreateUnknownSource()
    {
        $url = 'http://example.com/';

        $source = $this->sourceFactory->createUnknownFailedSource($url);

        $this->assertInstanceOf(Source::class, $source);
        $this->assertEquals($url, $source->getUrl());
        $this->assertEquals(Source::TYPE_UNAVAILABLE, $source->getType());
        $this->assertEquals('unknown:0', $source->getValue());
        $this->assertEquals(Source::FAILURE_TYPE_UNKNOWN, $source->getFailureType());
        $this->assertEquals(0, $source->getFailureCode());
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
