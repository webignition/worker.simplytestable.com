<?php

namespace App\Tests\Functional\EventListener;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\EventListener\TaskPerformedEventListener;
use App\Resque\Job\TaskReportCompletionJob;
use App\Tests\Services\ObjectPropertySetter;
use App\Services\Resque\QueueService;

class TaskPerformedEventListenerTest extends AbstractTaskEventListenerTest
{
    public function testInvoke()
    {
        $task = new Task();
        ObjectPropertySetter::setProperty($task, Task::class, 'id', self::TASK_ID);
        $taskEvent = new TaskEvent($task);

        $taskPerformedEventListener = self::$container->get(TaskPerformedEventListener::class);

        $resqueQueueService = \Mockery::spy(QueueService::class);

        ObjectPropertySetter::setProperty(
            $taskPerformedEventListener,
            TaskPerformedEventListener::class,
            'resqueQueueService',
            $resqueQueueService
        );

        $this->eventDispatcher->dispatch(TaskEvent::TYPE_PERFORMED, $taskEvent);

        $resqueQueueService
            ->shouldHaveReceived('enqueue')
            ->withArgs(function (TaskReportCompletionJob $taskReportCompletionJob) {
                $this->assertEquals(['id' => self::TASK_ID], $taskReportCompletionJob->args);

                return true;
            });
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
