<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskExaminer\WebPageTask;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Source;
use App\Services\SourceFactory;
use App\Services\TaskExaminer\WebPageTask\FailedSourceExaminer;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestTaskFactory;

class WebPageTaskFailedSourceExaminerTest extends AbstractBaseTestCase
{
    /**
     * @var FailedSourceExaminer
     */
    private $examiner;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->examiner = self::$container->get(FailedSourceExaminer::class);
    }

    /**
     * @dataProvider examineNoChangesDataProvider
     */
    public function testInvokeNoChanges(callable $taskCreator, bool $expectedPropagationIsStopped)
    {
        /* @var Task $task */
        $task = $taskCreator();

        $taskEvent = new TaskEvent($task);

        $this->examiner->__invoke($taskEvent);
        $this->assertEquals($expectedPropagationIsStopped, $taskEvent->isPropagationStopped());
    }

    /**
     * @dataProvider examineNoChangesDataProvider
     */
    public function testExamineNoChanges(callable $taskCreator, bool $expectedPropagationIsStopped)
    {
        /* @var Task $task */
        $task = $taskCreator();

        $taskState = $task->getState();

        $expectedReturnValue = !$expectedPropagationIsStopped;

        $returnValue = $this->examiner->examine($task);

        $this->assertEquals($expectedReturnValue, $returnValue);
        $this->assertEquals($taskState, $task->getState());
        $this->assertNull($task->getOutput());
    }

    public function examineNoChangesDataProvider()
    {
        return [
            'no sources' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );
                },
                'expectedPropagationIsStopped' => true,
            ],
            'no primary source' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $task->addSource(
                        $sourceFactory->createHttpFailedSource('http://example.com/404', 404)
                    );

                    return $task;
                },
                'expectedPropagationIsStopped' => true,
            ],
            'invalid primary source, is cached resource' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );
                    $testTaskFactory->addPrimaryCachedResourceSourceToTask($task, 'non-empty content');

                    return $task;
                },
                'expectedPropagationIsStopped' => false,
            ],
            'invalid primary source, is invalid content type' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $source = $sourceFactory->createInvalidSource(
                        $task->getUrl(),
                        Source::MESSAGE_INVALID_CONTENT_TYPE
                    );

                    $task->addSource($source);

                    return $task;
                },
                'expectedPropagationIsStopped' => false,
            ],
        ];
    }

    /**
     * @dataProvider examineSetsTaskAsFailedDataProvider
     */
    public function testInvokeSetsTaskAsFailed(callable $taskCreator)
    {
        /* @var Task $task */
        $task = $taskCreator();

        $taskEvent = new TaskEvent($task);

        $this->examiner->__invoke($taskEvent);

        $this->assertTrue($taskEvent->isPropagationStopped());
    }

    /**
     * @dataProvider examineSetsTaskAsFailedDataProvider
     */
    public function testExamineSetsTaskAsFailed(callable $taskCreator, array $expectedOutput)
    {
        /* @var Task $task */
        $task = $taskCreator();

        $this->assertEquals(Task::STATE_QUEUED, $task->getState());
        $this->assertNull($task->getOutput());

        $this->examiner->examine($task);

        $this->assertEquals(Task::STATE_FAILED_NO_RETRY_AVAILABLE, $task->getState());

        $taskOutput = $task->getOutput();
        $this->assertInstanceOf(Output::class, $taskOutput);
        $this->assertEquals(1, $taskOutput->getErrorCount());
        $this->assertEquals(0, $taskOutput->getWarningCount());
        $this->assertEquals(json_encode($expectedOutput), $taskOutput->getOutput());
    }

    public function examineSetsTaskAsFailedDataProvider()
    {
        return [
            'redirect loop' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $taskUrl = $task->getUrl();

                    $source = $sourceFactory->createHttpFailedSource($task->getUrl(), 301, [
                        'too_many_redirects' => true,
                        'is_redirect_loop' => true,
                        'history' => [
                            $taskUrl,
                            $taskUrl,
                            $taskUrl,
                            $taskUrl,
                            $taskUrl,
                            $taskUrl,
                        ],
                    ]);

                    $task->addSource($source);

                    return $task;
                },
                'expectedOutput' => [
                    'messages' => [
                        [
                            'message' => 'Redirect loop detected',
                            'messageId' => 'http-retrieval-redirect-loop',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'redirect limit reached' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $taskUrl = $task->getUrl();

                    $source = $sourceFactory->createHttpFailedSource($task->getUrl(), 301, [
                        'too_many_redirects' => true,
                        'is_redirect_loop' => false,
                        'history' => [
                            $taskUrl,
                            $taskUrl,
                            $taskUrl,
                            $taskUrl,
                            $taskUrl,
                            $taskUrl,
                        ],
                    ]);

                    $task->addSource($source);

                    return $task;
                },
                'expectedOutput' => [
                    'messages' => [
                        [
                            'message' => 'Redirect limit reached',
                            'messageId' => 'http-retrieval-redirect-limit-reached',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'curl 3' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $task->addSource($sourceFactory->createCurlFailedSource($task->getUrl(), 3));

                    return $task;
                },
                'expectedOutput' => [
                    'messages' => [
                        [
                            'message' => 'Invalid resource URL',
                            'messageId' => 'http-retrieval-curl-code-3',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'curl 6' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $task->addSource($sourceFactory->createCurlFailedSource($task->getUrl(), 6));

                    return $task;
                },
                'expectedOutput' => [
                    'messages' => [
                        [
                            'message' => 'DNS lookup failure resolving resource domain name',
                            'messageId' => 'http-retrieval-curl-code-6',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'curl 7' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $task->addSource($sourceFactory->createCurlFailedSource($task->getUrl(), 7));

                    return $task;
                },
                'expectedOutput' => [
                    'messages' => [
                        [
                            'message' => '',
                            'messageId' => 'http-retrieval-curl-code-7',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'curl 28' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $task->addSource($sourceFactory->createCurlFailedSource($task->getUrl(), 28));

                    return $task;
                },
                'expectedOutput' => [
                    'messages' => [
                        [
                            'message' => 'Timeout reached retrieving resource',
                            'messageId' => 'http-retrieval-curl-code-28',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'http 404' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $task->addSource($sourceFactory->createHttpFailedSource($task->getUrl(), 404));

                    return $task;
                },
                'expectedOutput' => [
                    'messages' => [
                        [
                            'message' => '',
                            'messageId' => 'http-retrieval-404',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'http 500' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $task->addSource($sourceFactory->createHttpFailedSource($task->getUrl(), 500));

                    return $task;
                },
                'expectedOutput' => [
                    'messages' => [
                        [
                            'message' => '',
                            'messageId' => 'http-retrieval-500',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'unknown' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $task->addSource($sourceFactory->createUnknownFailedSource($task->getUrl()));

                    return $task;
                },
                'expectedOutput' => [
                    'messages' => [
                        [
                            'message' => '',
                            'messageId' => 'http-retrieval-unknown-0',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider examineCompleteTaskDataProvider
     */
    public function testExamineCompleteTask(string $state)
    {
        $task = new Task();
        $task->setState($state);

        $this->assertFalse($this->examiner->examine($task));
    }

    public function examineCompleteTaskDataProvider(): array
    {
        return [
            'completed' => [
                'state' => Task::STATE_COMPLETED,
            ],
            'cancelled' => [
                'state' => Task::STATE_CANCELLED,
            ],
            'failed no retry available' => [
                'state' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
            ],
            'failed retry available' => [
                'state' => Task::STATE_FAILED_RETRY_AVAILABLE,
            ],
            'failed retry limit reached' => [
                'state' => Task::STATE_FAILED_RETRY_LIMIT_REACHED,
            ],
            'skipped' => [
                'state' => Task::STATE_SKIPPED,
            ],
        ];
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
