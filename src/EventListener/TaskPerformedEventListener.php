<?php

namespace App\EventListener;

use App\Event\TaskEvent;
use App\Resque\Job\TaskReportCompletionJob;
use App\Services\CachedResourceManager;
use App\Services\Resque\QueueService;
use App\Services\TaskService;
use App\Services\TaskUnusedCachedResourceRemover;

class TaskPerformedEventListener
{
    private $resqueQueueService;
    private $cachedResourceManager;
    private $taskService;
    private $taskUnusedCachedResourceRemover;

    public function __construct(
        QueueService $resqueQueueService,
        CachedResourceManager $cachedResourceManager,
        TaskService $taskService,
        TaskUnusedCachedResourceRemover $taskUnusedCachedResourceRemover
    ) {
        $this->resqueQueueService = $resqueQueueService;
        $this->cachedResourceManager = $cachedResourceManager;
        $this->taskService = $taskService;
        $this->taskUnusedCachedResourceRemover = $taskUnusedCachedResourceRemover;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        $task = $taskEvent->getTask();

        $this->resqueQueueService->enqueue(new TaskReportCompletionJob(['id' => $task->getId()]));
        $this->taskUnusedCachedResourceRemover->remove($task);
    }
}
