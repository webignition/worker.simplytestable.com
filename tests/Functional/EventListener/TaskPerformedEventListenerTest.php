<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\EventListener;

use App\Event\TaskEvent;
use App\EventListener\TaskPerformedEventListener;
use App\Resque\Job\TaskReportCompletionJob;
use App\Services\TaskUnusedCachedResourceRemover;
use App\Tests\Services\ObjectPropertySetter;
use App\Services\Resque\QueueService;
use App\Tests\Services\TestTaskFactory;

class TaskPerformedEventListenerTest extends AbstractTaskEventListenerTest
{
    public function testInvoke()
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $taskPerformedEventListener = self::$container->get(TaskPerformedEventListener::class);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
        $taskId = $task->getId();
        $taskEvent = new TaskEvent($task);

        $resqueQueueService = \Mockery::spy(QueueService::class);
        $taskUnusedCachedResourceRemover = \Mockery::spy(TaskUnusedCachedResourceRemover::class);

        ObjectPropertySetter::setProperty(
            $taskPerformedEventListener,
            TaskPerformedEventListener::class,
            'resqueQueueService',
            $resqueQueueService
        );

        ObjectPropertySetter::setProperty(
            $taskPerformedEventListener,
            TaskPerformedEventListener::class,
            'taskUnusedCachedResourceRemover',
            $taskUnusedCachedResourceRemover
        );

        $this->eventDispatcher->dispatch(TaskEvent::TYPE_PERFORMED, $taskEvent);

        $resqueQueueService
            ->shouldHaveReceived('enqueue')
            ->withArgs(function (TaskReportCompletionJob $taskReportCompletionJob) use ($taskId) {
                $this->assertEquals(['id' => $taskId], $taskReportCompletionJob->args);

                return true;
            });

        $taskUnusedCachedResourceRemover
            ->shouldHaveReceived('remove')
            ->with($task);
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
