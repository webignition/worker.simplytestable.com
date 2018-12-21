<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\Services;

use App\Entity\Task\Task;
use App\Model\TaskOutputValues;
use App\Model\TaskPerformerWebPageRetrieverResult;
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
        TaskOutputValues $expectedTaskOutputValues
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $result = $this->taskPerformerWebPageRetriever->retrieveWebPage($task);

        $this->assertInstanceOf(TaskPerformerWebPageRetrieverResult::class, $result);
        $this->assertNull($result->getWebPage());
        $this->assertEquals($expectedTaskState, $result->getTaskState());

        $taskOutputValues = $result->getTaskOutputValues();
        $this->assertInstanceOf(TaskOutputValues::class, $taskOutputValues);
        $this->assertEquals($expectedTaskOutputValues, $taskOutputValues);
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
                'expectedTaskOutputValues' => new TaskOutputValues(
                    [
                        'messages' => [
                            [
                                'message' => 'Redirect limit reached',
                                'messageId' => 'http-retrieval-redirect-limit-reached',
                                'type' => 'error',
                            ],
                        ],
                    ],
                    1,
                    0
                ),
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
                'expectedTaskOutputValues' => new TaskOutputValues(
                    [
                        'messages' => [
                            [
                                'message' => 'Redirect loop detected',
                                'messageId' => 'http-retrieval-redirect-loop',
                                'type' => 'error',
                            ],
                        ],
                    ],
                    1,
                    0
                ),
            ],
            'http 404' => [
                'httpResponseFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                ],
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    [
                        'messages' => [
                            [
                                'message' => 'Not Found',
                                'messageId' => 'http-retrieval-404',
                                'type' => 'error',
                            ],
                        ],
                    ],
                    1,
                    0
                ),
            ],
            'http 500' => [
                'httpResponseFixtures' => array_fill(0, 12, $internalServerErrorResponse),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    [
                        'messages' => [
                            [
                                'message' => 'Internal Server Error',
                                'messageId' => 'http-retrieval-500',
                                'type' => 'error',
                            ],
                        ],
                    ],
                    1,
                    0
                ),
            ],
            'curl 3' => [
                'httpResponseFixtures' => array_fill(0, 12, $curl3ConnectException),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    [
                        'messages' => [
                            [
                                'message' => 'Invalid resource URL',
                                'messageId' => 'http-retrieval-curl-code-3',
                                'type' => 'error',
                            ],
                        ],
                    ],
                    1,
                    0
                ),
            ],
            'curl 6' => [
                'httpResponseFixtures' => array_fill(0, 12, $curl6ConnectException),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    [
                        'messages' => [
                            [
                                'message' => 'DNS lookup failure resolving resource domain name',
                                'messageId' => 'http-retrieval-curl-code-6',
                                'type' => 'error',
                            ],
                        ],
                    ],
                    1,
                    0
                ),
            ],
            'curl 28' => [
                'httpResponseFixtures' => array_fill(0, 12, $curl28ConnectException),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    [
                        'messages' => [
                            [
                                'message' => 'Timeout reached retrieving resource',
                                'messageId' => 'http-retrieval-curl-code-28',
                                'type' => 'error',
                            ],
                        ],
                    ],
                    1,
                    0
                ),
            ],
            'curl unknown' => [
                'httpResponseFixtures' => array_fill(0, 12, $curl55ConnectException),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    [
                        'messages' => [
                            [
                                'message' => '',
                                'messageId' => 'http-retrieval-curl-code-55',
                                'type' => 'error',
                            ],
                        ],
                    ],
                    1,
                    0
                ),
            ],
            'incorrect resource type: application/pdf' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'application/pdf']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    null,
                    0,
                    0
                ),
            ],
            'incorrect resource type: text/javascript' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'text/javascript']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    null,
                    0,
                    0
                ),
            ],
            'incorrect resource type: application/javascript' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'application/javascript']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    null,
                    0,
                    0
                ),
            ],
            'incorrect resource type: application/xml' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'application/xml']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    null,
                    0,
                    0
                ),
            ],
            'incorrect resource type: text/xml' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'text/xml']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    null,
                    0,
                    0
                ),
            ],
            'empty content' => [
                'httpResponseFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html']),
                ],
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedTaskOutputValues' => new TaskOutputValues(
                    null,
                    0,
                    0
                ),
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

        $taskState = $task->getState();

        $this->assertTrue($task->isIncomplete());
        $this->assertEmpty($task->getOutput());

        $result = $this->taskPerformerWebPageRetriever->retrieveWebPage($task);

        $this->assertEquals($taskState, $result->getTaskState());
        $this->assertNull($result->getTaskOutputValues());

        $webPage = $result->getWebPage();

        $this->assertInstanceOf(WebPage::class, $webPage);
        $this->assertEquals($content, $webPage->getContent());
    }
}
