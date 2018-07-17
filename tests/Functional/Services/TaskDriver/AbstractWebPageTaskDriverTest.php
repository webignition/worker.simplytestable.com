<?php

namespace App\Tests\Functional\Services\TaskDriver;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use App\Services\TaskDriver\TaskDriver;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\TestTaskFactory;
use App\Tests\Services\HttpMockHandler;
use webignition\WebResource\Exception\TransportException;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

abstract class AbstractWebPageTaskDriverTest extends AbstractBaseTestCase
{
    /**
     * @var TestTaskFactory
     */
    protected $testTaskFactory;

    /**
     * @var HttpMockHandler
     */
    protected $httpMockHandler;

    /**
     * @var HttpHistoryContainer
     */
    protected $httpHistoryContainer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->testTaskFactory = new TestTaskFactory(self::$container);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
    }

    /**
     * @return TaskDriver
     */
    abstract protected function getTaskDriver();

    /**
     * @return string
     */
    abstract protected function getTaskTypeString();

    /**
     * @dataProvider cookiesDataProvider
     *
     * @param array $taskParameters
     * @param string $expectedRequestCookieHeader
     */
    abstract public function testSetCookiesOnRequests($taskParameters, $expectedRequestCookieHeader);

    /**
     * @dataProvider httpAuthDataProvider
     *
     * @param array $taskParameters
     * @param string $expectedRequestAuthorizationHeaderValue
     */
    abstract public function testSetHttpAuthenticationOnRequests(
        $taskParameters,
        $expectedRequestAuthorizationHeaderValue
    );

    public function testPerformNonCurlConnectException()
    {
        $connectException = new ConnectException('foo', new Request('GET', 'http://example.com'));

        $this->httpMockHandler->appendFixtures([
            $connectException,
            $connectException,
            $connectException,
            $connectException,
            $connectException,
            $connectException,
            $connectException,
            $connectException,
            $connectException,
            $connectException,
            $connectException,
            $connectException,
        ]);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode(0);

        $this->getTaskDriver()->perform($task);
    }

    /**
     * @dataProvider performBadWebResourceDataProvider
     *
     * @param string[] $httpResponseFixtures
     * @param bool $expectedWebResourceRetrievalHasSucceeded
     * @param bool $expectedIsRetryable
     * @param int $expectedErrorCount
     * @param string $expectedTaskOutput
     */
    public function testPerformBadWebResource(
        $httpResponseFixtures,
        $expectedWebResourceRetrievalHasSucceeded,
        $expectedIsRetryable,
        $expectedErrorCount,
        $expectedTaskOutput
    ) {
        $this->httpMockHandler->appendFixtures($httpResponseFixtures);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $taskDriver = $this->getTaskDriver();

        $taskDriverResponse = $taskDriver->perform($task);

        $this->assertEquals($expectedWebResourceRetrievalHasSucceeded, $taskDriverResponse->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $taskDriverResponse->isRetryable());
        $this->assertEquals($expectedErrorCount, $taskDriverResponse->getErrorCount());

        $this->assertEquals(
            $expectedTaskOutput,
            json_decode($taskDriverResponse->getTaskOutput()->getOutput(), true)
        );
    }

    public function performBadWebResourceDataProvider()
    {
        $notFoundResponse = new Response(404);
        $internalServerErrorResponse = new Response(500);
        $curl3ConnectException = ConnectExceptionFactory::create('CURL/3: foo');
        $curl6ConnectException = ConnectExceptionFactory::create('CURL/6: foo');
        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28: foo');
        $curl55ConnectException = ConnectExceptionFactory::create('CURL/55: foo');

        return [
            'http too many redirects' => [
                'httpResponseFixtures' => [
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '1']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '2']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '3']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '4']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '5']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '6']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '1']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '2']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '3']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '4']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '5']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '6']),
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Redirect limit reached',
                            'messageId' => 'http-retrieval-redirect-limit-reached',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'http redirect loop' => [
                'httpResponseFixtures' => [
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '1']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '2']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '3']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL]),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '1']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '2']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '1']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '2']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '3']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL]),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '1']),
                    new Response(301, ['location' => TestTaskFactory::DEFAULT_TASK_URL . '2']),

                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Redirect loop detected',
                            'messageId' => 'http-retrieval-redirect-loop',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'http 404' => [
                'httpResponseFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Not Found',
                            'messageId' => 'http-retrieval-404',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'http 500' => [
                'httpResponseFixtures' => [
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,

                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Internal Server Error',
                            'messageId' => 'http-retrieval-500',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'curl 3' => [
                'httpResponseFixtures' => [
                    $curl3ConnectException,
                    $curl3ConnectException,
                    $curl3ConnectException,
                    $curl3ConnectException,
                    $curl3ConnectException,
                    $curl3ConnectException,
                    $curl3ConnectException,
                    $curl3ConnectException,
                    $curl3ConnectException,
                    $curl3ConnectException,
                    $curl3ConnectException,
                    $curl3ConnectException,
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Invalid resource URL',
                            'messageId' => 'http-retrieval-curl-code-3',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'curl 6' => [
                'httpResponseFixtures' => [
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'DNS lookup failure resolving resource domain name',
                            'messageId' => 'http-retrieval-curl-code-6',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'curl 28' => [
                'httpResponseFixtures' => [
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Timeout reached retrieving resource',
                            'messageId' => 'http-retrieval-curl-code-28',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'curl unknown' => [
                'httpResponseFixtures' => [
                    $curl55ConnectException,
                    $curl55ConnectException,
                    $curl55ConnectException,
                    $curl55ConnectException,
                    $curl55ConnectException,
                    $curl55ConnectException,
                    $curl55ConnectException,
                    $curl55ConnectException,
                    $curl55ConnectException,
                    $curl55ConnectException,
                    $curl55ConnectException,
                    $curl55ConnectException,
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => '',
                            'messageId' => 'http-retrieval-curl-code-55',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'incorrect resource type' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'application/pdf']),
                ],
                'expectedWebResourceRetrievalHasSucceeded' => true,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
            'empty content' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html']),
                ],
                'expectedWebResourceRetrievalHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
        ];
    }

    /**
     * @return array
     */
    public function cookiesDataProvider()
    {
        return [
            'no cookies' => [
                'taskParameters' => [],
                'expectedRequestCookieHeader' => '',
            ],
            'single cookie' => [
                'taskParameters' => [
                    'cookies' => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                            'Domain' => '.example.com',
                        ],
                    ],
                ],
                'expectedRequestCookieHeader' => 'foo=bar',
            ],
            'multiple cookies' => [
                'taskParameters' => [
                    'cookies' => [
                        [
                            'Name' => 'foo1',
                            'Value' => 'bar1',
                            'Domain' => '.example.com',
                        ],
                        [
                            'Name' => 'foo2',
                            'Value' => 'bar2',
                            'Domain' => 'foo2.example.com',
                        ],
                        [
                            'Name' => 'foo3',
                            'Value' => 'bar3',
                            'Domain' => '.example.com',
                        ],
                    ],
                ],
                'expectedRequestCookieHeader' => 'foo1=bar1; foo3=bar3',
            ],
        ];
    }

    /**
     * @return array
     */
    public function httpAuthDataProvider()
    {
        return [
            'no auth' => [
                'taskParameters' => [],
                'expectedRequestAuthorizationHeaderValue' => '',
            ],
            'has auth' => [
                'taskParameters' => [
                    'http-auth-username' => 'foouser',
                    'http-auth-password' => 'foopassword',
                ],
                'expectedRequestAuthorizationHeaderValue' => 'foouser:foopassword',
            ],
        ];
    }

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->httpMockHandler->count());
    }
}
