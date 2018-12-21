<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\Services;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Services\TaskPerformerWebPageRetriever;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\WebPage\WebPage;

class TaskPerformerWebPageRetrieverTest extends AbstractBaseTestCase
{
    /**
     * @var TaskPerformerWebPageRetriever
     */
    private $taskPerformerWebPageRetriever;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskPerformerWebPageRetriever = self::$container->get(TaskPerformerWebPageRetriever::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
    }

    /**
     * @dataProvider retrieveWebPageFailureDataProvider
     */
    public function testRetrieveWebPageFailure(
        array $httpFixtures,
        string $expectedTaskState,
        int $expectedErrorCount,
        ?array $expectedTaskOutput
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $this->taskPerformerWebPageRetriever->retrieveWebPage($task);

        $this->assertEquals($expectedTaskState, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals($expectedErrorCount, $output->getErrorCount());

        $this->assertEquals(
            $expectedTaskOutput,
            json_decode($output->getOutput(), true)
        );
    }

    public function retrieveWebPageFailureDataProvider(): array
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

    public function testRetrieveWebPageNonCurlConnectException()
    {
        $connectException = new ConnectException('foo', new Request('GET', 'http://example.com'));

        $this->httpMockHandler->appendFixtures(array_fill(0, 12, $connectException));

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode(0);

        $this->taskPerformerWebPageRetriever->retrieveWebPage($task);
    }

    public function testRetrieveWebPageSuccess()
    {
        $content = 'web page content';

        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], $content),
        ]);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $this->assertTrue($task->isIncomplete());
        $this->assertEmpty($task->getOutput());

        $webPage = $this->taskPerformerWebPageRetriever->retrieveWebPage($task);

        $this->assertInstanceOf(WebPage::class, $webPage);
        $this->assertEquals($content, $webPage->getContent());

        $this->assertTrue($task->isIncomplete());
        $this->assertEmpty($task->getOutput());
    }
}
