<?php

namespace App\Tests\Unit\Services;

use App\Model\Source;
use App\Services\SourceFactory;
use App\Services\TaskOutputMessageFactory;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class TaskOutputMessageFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createOutputMessageCollectionFromSourceDataProvider
     *
     * @param Source $source
     * @param string $expectedOutputMessage
     * @param string $expectedOutputMessageId
     */
    public function testCreateOutputMessageCollectionFromSource(
        Source $source,
        string $expectedOutputMessage,
        string $expectedOutputMessageId
    ) {
        $httpHistoryContainer = \Mockery::mock(HttpHistoryContainer::class);
        $factory = new TaskOutputMessageFactory($httpHistoryContainer);

        $output = $factory->createOutputMessageCollectionFromSource($source);
        $this->assertInternalType('array', $output);
        $this->assertSingleMessagOutputMessageCollection(
            $output,
            $expectedOutputMessage,
            $expectedOutputMessageId
        );
    }

    public function createOutputMessageCollectionFromSourceDataProvider(): array
    {
        $sourceFactory = new SourceFactory();

        return [
            'redirect loop' => [
                'source' => $sourceFactory->createHttpFailedSource(
                    'http://example.com',
                    301,
                    [
                        'too_many_redirects' => true,
                        'is_redirect_loop' => true,
                        'history' => [],
                    ]
                ),
                'expectedMessage' => 'Redirect loop detected',
                'expectedMessageId' => 'http-retrieval-redirect-loop',
            ],
            'too many redirects' => [
                'source' => $sourceFactory->createHttpFailedSource(
                    'http://example.com',
                    301,
                    [
                        'too_many_redirects' => true,
                        'is_redirect_loop' => false,
                        'history' => [],
                    ]
                ),
                'expectedMessage' => 'Redirect limit reached',
                'expectedMessageId' => 'http-retrieval-redirect-limit-reached',
            ],
            'http 404' => [
                'source' => $sourceFactory->createHttpFailedSource(
                    'http://example.com',
                    404,
                    []
                ),
                'expectedMessage' => '',
                'expectedMessageId' => 'http-retrieval-404',
            ],
            'curl 3' => [
                'source' => $sourceFactory->createCurlFailedSource(
                    'http://example.com',
                    3
                ),
                'expectedMessage' => 'Invalid resource URL',
                'expectedMessageId' => 'http-retrieval-curl-code-3',
            ],
            'curl 6' => [
                'source' => $sourceFactory->createCurlFailedSource(
                    'http://example.com',
                    6
                ),
                'expectedMessage' => 'DNS lookup failure resolving resource domain name',
                'expectedMessageId' => 'http-retrieval-curl-code-6',
            ],
            'curl 7' => [
                'source' => $sourceFactory->createCurlFailedSource(
                    'http://example.com',
                    7
                ),
                'expectedMessage' => '',
                'expectedMessageId' => 'http-retrieval-curl-code-7',
            ],
            'curl 28' => [
                'source' => $sourceFactory->createCurlFailedSource(
                    'http://example.com',
                    28
                ),
                'expectedMessage' => 'Timeout reached retrieving resource',
                'expectedMessageId' => 'http-retrieval-curl-code-28',
            ],
            'unknown' => [
                'source' => $sourceFactory->createUnknownFailedSource(
                    'http://example.com'
                ),
                'expectedMessage' => '',
                'expectedMessageId' => 'http-retrieval-unknown-0',
            ],
        ];
    }

    private function assertSingleMessagOutputMessageCollection(
        array $output,
        string $expectedMessage,
        string $expectedMessageId
    ) {
        $this->assertEquals(['messages'], array_keys($output));

        $messages = $output['messages'];
        $this->assertInternalType('array', $messages);
        $this->assertCount(1, $messages);

        $message = $messages[0];
        $this->assertInternalType('array', $message);
        $this->assertEquals(
            [
                'message' => $expectedMessage,
                'messageId' => $expectedMessageId,
                'type' => 'error',
            ],
            $message
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
