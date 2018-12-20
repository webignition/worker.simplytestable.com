<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Services\TaskTypePerformer\TaskTypePerformerInterface;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Services\HttpMockHandler;
use webignition\WebResource\Exception\TransportException;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

abstract class AbstractWebPageTaskTypePerformerTest extends AbstractBaseTestCase
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

        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
    }

    abstract protected function getTaskTypePerformer(): TaskTypePerformerInterface;
    abstract protected function getTaskTypeString():string;

    /**
     * @dataProvider cookiesDataProvider
     */
    abstract public function testSetCookiesOnRequests(array $taskParameters, string $expectedRequestCookieHeader);

    /**
     * @dataProvider httpAuthDataProvider
     */
    abstract public function testSetHttpAuthenticationOnRequests(
        array $taskParameters,
        string $expectedRequestAuthorizationHeaderValue
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

        $this->getTaskTypePerformer()->perform($task);
    }

    /**
     * @dataProvider performBadWebResourceDataProvider
     */
    public function testPerformBadWebResource(
        $httpResponseFixtures,
        $expectedTaskState,
        $expectedErrorCount,
        $expectedTaskOutput
    ) {
        $this->httpMockHandler->appendFixtures($httpResponseFixtures);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $taskTypePerformer = $this->getTaskTypePerformer();
        $taskTypePerformer->perform($task);

        $this->assertEquals($expectedTaskState, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals($expectedErrorCount, $output->getErrorCount());

        $this->assertEquals(
            $expectedTaskOutput,
            json_decode($output->getOutput(), true)
        );
    }

    public function performBadWebResourceDataProvider(): array
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
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
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
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
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
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
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
                'httpResponseFixtures' => array_fill(0, 12, $internalServerErrorResponse),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
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
                'httpResponseFixtures' => array_fill(0, 12, $curl3ConnectException),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
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
                'httpResponseFixtures' => array_fill(0, 12, $curl6ConnectException),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
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
                'httpResponseFixtures' => array_fill(0, 12, $curl28ConnectException),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
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
                'httpResponseFixtures' => array_fill(0, 12, $curl55ConnectException),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
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
            'incorrect resource type: application/pdf' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'application/pdf']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
            'incorrect resource type: text/javascript' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'text/javascript']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
            'incorrect resource type: application/javascript' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'application/javascript']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
            'incorrect resource type: application/xml' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'application/xml']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
            'incorrect resource type: text/xml' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'text/xml']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
            'empty content' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
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
