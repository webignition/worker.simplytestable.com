<?php

namespace App\EventListener;

use App\Event\TaskEvent;
use App\Resque\Job\TaskPerformJob;
use App\Services\Resque\QueueService;

class TaskPreparedEventListener
{
    private $resqueQueueService;

    public function __construct(QueueService $resqueQueueService)
    {
        $this->resqueQueueService = $resqueQueueService;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        $this->resqueQueueService->enqueue(new TaskPerformJob(['id' => $taskEvent->getTask()->getId()]));
    }
}
