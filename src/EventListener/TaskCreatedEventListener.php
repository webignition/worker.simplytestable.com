<?php

namespace App\EventListener;

use App\Event\TaskEvent;
use App\Resque\Job\TaskPrepareJob;
use App\Services\Resque\QueueService;

class TaskCreatedEventListener
{
    private $resqueQueueService;

    public function __construct(QueueService $resqueQueueService)
    {
        $this->resqueQueueService = $resqueQueueService;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        $this->resqueQueueService->enqueue(new TaskPrepareJob(['id' => $taskEvent->getTask()->getId()]));
    }
}
