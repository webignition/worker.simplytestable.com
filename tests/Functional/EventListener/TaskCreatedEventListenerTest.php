<?php

namespace App\Tests\Functional\EventListener;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\EventListener\TaskCreatedEventListener;
use App\Resque\Job\TaskPrepareJob;
use App\Services\Resque\QueueService;
use App\Tests\Services\ObjectReflector;

class TaskCreatedEventListenerTest extends AbstractTaskEventListenerTest
{
    public function testInvoke()
    {
        $task = new Task();
        ObjectReflector::setProperty($task, Task::class, 'id', self::TASK_ID);
        $taskEvent = new TaskEvent($task);

        $taskCreatedEventListener = self::$container->get(TaskCreatedEventListener::class);

        $resqueQueueService = \Mockery::spy(QueueService::class);

        ObjectReflector::setProperty(
            $taskCreatedEventListener,
            TaskCreatedEventListener::class,
            'resqueQueueService',
            $resqueQueueService
        );

        $this->eventDispatcher->dispatch(TaskEvent::TYPE_CREATED, $taskEvent);

        $resqueQueueService
            ->shouldHaveReceived('enqueue')
            ->withArgs(function (TaskPrepareJob $taskPrepareJob) {
                $this->assertEquals(['id' => self::TASK_ID], $taskPrepareJob->args);

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
