<?php

namespace App\Tests\Functional\EventListener;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\EventListener\TaskPreparedEventListener;
use App\Resque\Job\TaskPerformJob;
use App\Services\Resque\QueueService;
use App\Tests\Services\ObjectReflector;

class TaskPreparedEventListenerTest extends AbstractTaskEventListenerTest
{
    public function testInvoke()
    {
        $task = new Task();
        ObjectReflector::setProperty($task, Task::class, 'id', self::TASK_ID);
        $taskEvent = new TaskEvent($task);

        $taskPreparedEventListener = self::$container->get(TaskPreparedEventListener::class);

        $resqueQueueService = \Mockery::spy(QueueService::class);

        ObjectReflector::setProperty(
            $taskPreparedEventListener,
            TaskPreparedEventListener::class,
            'resqueQueueService',
            $resqueQueueService
        );

        $this->eventDispatcher->dispatch(TaskEvent::TYPE_PREPARED, $taskEvent);

        $resqueQueueService
            ->shouldHaveReceived('enqueue')
            ->withArgs(function (TaskPerformJob $taskPerformJob) {
                $this->assertEquals(['id' => self::TASK_ID], $taskPerformJob->args);

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
