<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\EventListener;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\EventListener\TaskPerformedEventListener;
use App\Resque\Job\TaskReportCompletionJob;
use App\Services\TaskUnusedCachedResourceRemover;
use App\Services\Resque\QueueService;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TestTaskFactory;
use Mockery\MockInterface;

class TaskPerformedEventListenerTest extends AbstractTaskEventListenerTest
{
    /**
     * @dataProvider invokeDataProvider
     */
    public function testInvoke(callable $taskCreator, callable $resqueQueueServiceCreator)
    {
        /* @var Task $task */
        $task = $taskCreator();

        $taskPerformedEventListener = self::$container->get(TaskPerformedEventListener::class);

        $taskId = $task->getId();
        $taskEvent = new TaskEvent($task);

        $resqueQueueService = $resqueQueueServiceCreator($taskId);

        $taskUnusedCachedResourceRemover = \Mockery::spy(TaskUnusedCachedResourceRemover::class);

        ObjectReflector::setProperty(
            $taskPerformedEventListener,
            TaskPerformedEventListener::class,
            'resqueQueueService',
            $resqueQueueService
        );

        ObjectReflector::setProperty(
            $taskPerformedEventListener,
            TaskPerformedEventListener::class,
            'taskUnusedCachedResourceRemover',
            $taskUnusedCachedResourceRemover
        );

        $this->eventDispatcher->dispatch(TaskEvent::TYPE_PERFORMED, $taskEvent);

        $taskUnusedCachedResourceRemover
            ->shouldHaveReceived('remove')
            ->with($task);
    }

    public function invokeDataProvider(): array
    {
        return [
            'parent task' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
                },
                'resqueQueueServiceCreator' => function (int $taskId): MockInterface {
                    $resqueQueueService = \Mockery::mock(QueueService::class);

                    $resqueQueueService
                        ->shouldReceive('enqueue')
                        ->once()
                        ->withArgs(function (TaskReportCompletionJob $taskReportCompletionJob) use ($taskId) {
                            $this->assertEquals(['id' => $taskId], $taskReportCompletionJob->args);

                            return true;
                        });

                    return $resqueQueueService;
                },
            ],
            'child task' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    $parentTask = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
                    $childTask = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
                    $childTask->setParentTask($parentTask);

                    return $childTask;
                },
                'resqueQueueServiceCreator' => function (): MockInterface {
                    $resqueQueueService = \Mockery::mock(QueueService::class);

                    $resqueQueueService
                        ->shouldNotReceive('enqueue');

                    return $resqueQueueService;
                },
            ],
        ];
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
