<?php
/** @noinspection PhpDocSignatureInspection */

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
        $requestHash = 'cached-resource-id';

        $cachedResource = \Mockery::mock(CachedResource::class);
        $cachedResource
            ->shouldReceive('getUrl')
            ->atLeast()
            ->once()
            ->andReturn($url);

        $cachedResource
            ->shouldReceive('getRequestHash')
            ->atLeast()
            ->once()
            ->andReturn($requestHash);

        $source = $this->sourceFactory->fromCachedResource($cachedResource);

        $this->assertInstanceOf(Source::class, $source);
        $this->assertEquals($url, $source->getUrl());
        $this->assertEquals(Source::TYPE_CACHED_RESOURCE, $source->getType());
        $this->assertEquals($requestHash, $source->getValue());
        $this->assertNull($source->getFailureType());
        $this->assertNull($source->getFailureCode());
        $this->assertTrue($source->isCachedResource());
        $this->assertFalse($source->isUnavailable());
        $this->assertFalse($source->isInvalid());
    }

    /**
     * @dataProvider createHttpFailedSourceDataProvider
     */
    public function testCreateHttpFailedSource(string $url, int $statusCode, array $context, string $expectedValue)
    {
        $source = $this->sourceFactory->createHttpFailedSource($url, $statusCode, $context);

        $this->assertInstanceOf(Source::class, $source);
        $this->assertEquals($url, $source->getUrl());
        $this->assertEquals(Source::TYPE_UNAVAILABLE, $source->getType());
        $this->assertEquals($expectedValue, $source->getValue());
        $this->assertEquals(Source::FAILURE_TYPE_HTTP, $source->getFailureType());
        $this->assertEquals($statusCode, $source->getFailureCode());
        $this->assertEquals($context, $source->getContext());
        $this->assertFalse($source->isCachedResource());
        $this->assertTrue($source->isUnavailable());
        $this->assertFalse($source->isInvalid());
    }

    public function createHttpFailedSourceDataProvider(): array
    {
        return [
            '404 without context' => [
                'url' => 'http://example.com/404',
                'statusCode' => 404,
                'context' => [],
                'expectedValue' => 'http:404',
            ],
            '301 with context, redirect loop' => [
                'url' => 'http://example.com/301',
                'statusCode' => 301,
                'context' => [
                    'too_many_redirects' => true,
                    'is_redirect_loop' => true,
                    'history' => [
                        'http://example.com/301',
                        'http://example.com/301',
                        'http://example.com/301',
                        'http://example.com/301',
                        'http://example.com/301',
                        'http://example.com/301',
                    ],
                ],
                'expectedValue' => 'http:301',
            ],
            '301 with context, too many redirects' => [
                'url' => 'http://example.com/301',
                'statusCode' => 301,
                'context' => [
                    'too_many_redirects' => true,
                    'is_redirect_loop' => false,
                    'history' => [
                        'http://example.com/301',
                        'http://example.com/301/1',
                        'http://example.com/301/2',
                        'http://example.com/301/3',
                        'http://example.com/301/4',
                        'http://example.com/301/5',
                    ],
                ],
                'expectedValue' => 'http:301',
            ],
        ];
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
        $this->assertFalse($source->isCachedResource());
        $this->assertTrue($source->isUnavailable());
        $this->assertFalse($source->isInvalid());
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
        $this->assertFalse($source->isCachedResource());
        $this->assertTrue($source->isUnavailable());
        $this->assertFalse($source->isInvalid());
    }

    public function createInvalidSource()
    {
        $url = 'http://example.com/';

        $source = $this->sourceFactory->createInvalidSource($url, Source::MESSAGE_INVALID_CONTENT_TYPE);

        $this->assertInstanceOf(Source::class, $source);
        $this->assertEquals($url, $source->getUrl());
        $this->assertEquals(Source::TYPE_INVALID, $source->getType());
        $this->assertEquals('invalid:invalid-content-type', $source->getValue());
        $this->assertEquals(Source::FAILURE_TYPE_UNKNOWN, $source->getFailureType());
        $this->assertEquals(0, $source->getFailureCode());
        $this->assertFalse($source->isCachedResource());
        $this->assertFalse($source->isUnavailable());
        $this->assertTrue($source->isInvalid());
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
