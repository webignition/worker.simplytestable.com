<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskExaminer\WebPageTask;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Source;
use App\Services\SourceFactory;
use App\Services\TaskExaminer\WebPageTask\InvalidSourceExaminer;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestTaskFactory;

class WebPageTaskInvalidSourceExaminerTest extends AbstractBaseTestCase
{
    /**
     * @var InvalidSourceExaminer
     */
    private $examiner;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->examiner = self::$container->get(InvalidSourceExaminer::class);
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
            'invalid primary source, not invalid content type' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $task->addSource(
                        $sourceFactory->createHttpFailedSource($task->getUrl(), 404)
                    );

                    return $task;
                },
                'expectedPropagationIsStopped' => false,
            ],
            'non-empty cached resource' => [
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
        ];
    }

    /**
     * @dataProvider examineSetsTaskAsSkippedDataProvider
     */
    public function testInvokeSetsTaskAsSkipped(callable $taskCreator)
    {
        /* @var Task $task */
        $task = $taskCreator();
        $taskEvent = new TaskEvent($task);

        $this->examiner->__invoke($taskEvent);

        $this->assertTrue($taskEvent->isPropagationStopped());
    }

    /**
     * @dataProvider examineSetsTaskAsSkippedDataProvider
     */
    public function testExamineSetsTaskAsSkipped(callable $taskCreator)
    {
        /* @var Task $task */
        $task = $taskCreator();

        $this->assertEquals(Task::STATE_QUEUED, $task->getState());
        $this->assertNull($task->getOutput());

        $this->examiner->examine($task);

        $this->assertEquals(Task::STATE_SKIPPED, $task->getState());

        $taskOutput = $task->getOutput();
        $this->assertInstanceOf(Output::class, $taskOutput);
        $this->assertEquals(0, $taskOutput->getErrorCount());
        $this->assertEquals(0, $taskOutput->getWarningCount());
        $this->assertEquals('', $taskOutput->getOutput());
    }

    public function examineSetsTaskAsSkippedDataProvider()
    {
        return [
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
            ],
            'empty cached resource' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );
                    $testTaskFactory->addPrimaryCachedResourceSourceToTask($task, '');

                    return $task;
                },
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
