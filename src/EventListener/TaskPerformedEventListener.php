<?php

namespace App\EventListener;

use App\Event\TaskEvent;
use App\Resque\Job\TaskReportCompletionJob;
use App\Services\Resque\QueueService;

class TaskPerformedEventListener
{
    private $resqueQueueService;

    public function __construct(QueueService $resqueQueueService)
    {
        $this->resqueQueueService = $resqueQueueService;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        $this->resqueQueueService->enqueue(new TaskReportCompletionJob(['id' => $taskEvent->getTask()->getId()]));
    }
}
