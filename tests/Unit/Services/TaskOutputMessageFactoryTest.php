<?php

namespace App\Tests\Unit\Services;

use App\Model\Source;
use App\Services\SourceFactory;
use App\Services\TaskOutputMessageFactory;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use webignition\WebResource\Exception\TransportException;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class TaskOutputMessageFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createHttpExceptionOutputMessageCollectionDataProvider
     *
     * @param string $message
     * @param int $statusCode
     * @param string $expectedMessage
     * @param string $expectedMessageId
     */
    public function testCreateHttpExceptionOutputMessageCollection(
        string $message,
        int $statusCode,
        string $expectedMessage,
        string $expectedMessageId
    ) {
        $httpHistoryContainer = \Mockery::mock(HttpHistoryContainer::class);
        $factory = new TaskOutputMessageFactory($httpHistoryContainer);

        $output = $factory->createHttpExceptionOutputMessageCollection($message, $statusCode);
        $this->assertInternalType('array', $output);
        $this->assertSingleMessagOutputMessageCollection($output, $expectedMessage, $expectedMessageId);
    }

    public function createHttpExceptionOutputMessageCollectionDataProvider(): array
    {
        return [
            '404' => [
                'message' => 'Not Found',
                'statusCode' => 404,
                'expectedMessage' => 'Not Found',
                'expectedMessageId' => 'http-retrieval-404',
            ],
            '500' => [
                'message' => 'Internal Server Error',
                'statusCode' => 500,
                'expectedMessage' => 'Internal Server Error',
                'expectedMessageId' => 'http-retrieval-500',
            ],
        ];
    }

    /**
     * @dataProvider createTransportExceptionOutputMessageCollectionDataProvider
     *
     * @param TransportException $transportException
     * @param string $expectedMessage
     * @param string $expectedMessageId
     */
    public function testCreateTransportExceptionOutputMessageCollectionForCurlError(
        TransportException $transportException,
        string $expectedMessage,
        string $expectedMessageId
    ) {
        $httpHistoryContainer = \Mockery::mock(HttpHistoryContainer::class);
        $factory = new TaskOutputMessageFactory($httpHistoryContainer);

        $output = $factory->createTransportExceptionOutputMessageCollection($transportException);
        $this->assertInternalType('array', $output);
        $this->assertSingleMessagOutputMessageCollection($output, $expectedMessage, $expectedMessageId);
    }

    public function createTransportExceptionOutputMessageCollectionDataProvider(): array
    {
        return [
            '3 invalid url' => [
                'transportException' => $this->createTransportException($this->createConnectException(
                    'cURL error 3: foo'
                )),
                'expectedMessage' => 'Invalid resource URL',
                'expectedMessageId' => 'http-retrieval-curl-code-3',
            ],
            '6 dns lookup failure' => [
                'transportException' => $this->createTransportException($this->createConnectException(
                    'cURL error 6: foo'
                )),
                'expectedMessage' => 'DNS lookup failure resolving resource domain name',
                'expectedMessageId' => 'http-retrieval-curl-code-6',
            ],
            '7 couldn\'t connect' => [
                'transportException' => $this->createTransportException($this->createConnectException(
                    'cURL error 7: foo'
                )),
                'expectedMessage' => '',
                'expectedMessageId' => 'http-retrieval-curl-code-7',
            ],
            '28 operation timed out' => [
                'transportException' => $this->createTransportException($this->createConnectException(
                    'cURL error 28: foo'
                )),
                'expectedMessage' => 'Timeout reached retrieving resource',
                'expectedMessageId' => 'http-retrieval-curl-code-28',
            ],
        ];
    }

    /**
     * @dataProvider createTransportExceptionOutputMessageCollectionForTooManyRedirectsDataProvider
     *
     * @param array $requestUrlsAsStrings
     * @param TransportException $transportException
     * @param string $expectedMessage
     * @param string $expectedMessageId
     */
    public function testCreateTransportExceptionOutputMessageCollectionForTooManyRedirects(
        array $requestUrlsAsStrings,
        TransportException $transportException,
        string $expectedMessage,
        string $expectedMessageId
    ) {
        $httpHistoryContainer = \Mockery::mock(HttpHistoryContainer::class);
        $httpHistoryContainer
            ->shouldReceive('getResponses')
            ->andReturn([new Response(301)]);

        $httpHistoryContainer
            ->shouldReceive('getRequestUrlsAsStrings')
            ->andReturn($requestUrlsAsStrings);

        $factory = new TaskOutputMessageFactory($httpHistoryContainer);

        $output = $factory->createTransportExceptionOutputMessageCollection($transportException);
        $this->assertInternalType('array', $output);
        $this->assertSingleMessagOutputMessageCollection($output, $expectedMessage, $expectedMessageId);
    }

    public function createTransportExceptionOutputMessageCollectionForTooManyRedirectsDataProvider(): array
    {
        return [
            'not redirect loop' => [
                'requestUrlsAsStrings' => [
                    'http://example.com/',
                    'http://example.com/1',
                    'http://example.com/2',
                    'http://example.com/3',
                    'http://example.com/4',
                    'http://example.com/5',
                    'http://example.com/',
                    'http://example.com/1',
                    'http://example.com/2',
                    'http://example.com/3',
                    'http://example.com/4',
                    'http://example.com/5',
                ],
                'transportException' => $this->createTransportException($this->createTooManyRedirectsException()),
                'expectedMessage' => 'Redirect limit reached',
                'expectedMessageId' => 'http-retrieval-redirect-limit-reached',
            ],
            'is redirect loop' => [
                'requestUrlsAsStrings' => [
                    'http://example.com/',
                    'http://example.com/1',
                    'http://example.com/1',
                    'http://example.com/1',
                    'http://example.com/1',
                    'http://example.com/1',
                    'http://example.com/',
                    'http://example.com/1',
                    'http://example.com/1',
                    'http://example.com/1',
                    'http://example.com/1',
                    'http://example.com/1',
                ],
                'transportException' => $this->createTransportException($this->createTooManyRedirectsException()),
                'expectedMessage' => 'Redirect loop detected',
                'expectedMessageId' => 'http-retrieval-redirect-loop',
            ],
        ];
    }

    public function testCreateTransportExceptionOutputMessageCollectionForInvalidTooManyRedirects()
    {
        $httpHistoryContainer = \Mockery::mock(HttpHistoryContainer::class);
        $httpHistoryContainer
            ->shouldReceive('getResponses')
            ->andReturn([
                new Response(301),
                new Response(200),
            ]);

        $httpHistoryContainer
            ->shouldReceive('getRequestUrlsAsStrings')
            ->andReturn([]);

        $factory = new TaskOutputMessageFactory($httpHistoryContainer);

        $transportException = $this->createTransportException($this->createTooManyRedirectsException());

        $output = $factory->createTransportExceptionOutputMessageCollection($transportException);
        $this->assertInternalType('array', $output);
        $this->assertSingleMessagOutputMessageCollection(
            $output,
            'Redirect limit reached',
            'http-retrieval-redirect-limit-reached'
        );
    }

    public function testCreateTransportExceptionOutputMessageCollectionForHttpError()
    {
        $httpHistoryContainer = \Mockery::mock(HttpHistoryContainer::class);
        $factory = new TaskOutputMessageFactory($httpHistoryContainer);

        $request = \Mockery::mock(RequestInterface::class);

        $transportException = $this->createTransportException(new BadResponseException('Not Found', $request));

        $output = $factory->createTransportExceptionOutputMessageCollection($transportException);
        $this->assertInternalType('array', $output);
        $this->assertSingleMessagOutputMessageCollection(
            $output,
            '',
            'http-retrieval-curl-code-0'
        );
    }

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

    private function createTransportException(RequestException $requestException): TransportException
    {
        $request = \Mockery::mock(RequestInterface::class);
        $transportException = new TransportException($request, $requestException);

        return $transportException;
    }

    private function createConnectException(string $message): ConnectException
    {
        $request = \Mockery::mock(RequestInterface::class);

        $connectException = new ConnectException($message, $request);

        return $connectException;
    }

    private function createTooManyRedirectsException(): TooManyRedirectsException
    {
        $message = 'Will not follow more than 5 redirects';
        $request = \Mockery::mock(RequestInterface::class);

        $tooManyRedirectsException = new TooManyRedirectsException($message, $request);

        return $tooManyRedirectsException;
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
