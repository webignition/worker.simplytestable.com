<?php

namespace App\EventListener;

use App\Event\TaskEvent;
use App\Resque\Job\TaskReportCompletionJob;
use App\Services\CachedResourceManager;
use App\Services\Resque\QueueService;
use App\Services\TaskService;

class TaskPerformedEventListener
{
    private $resqueQueueService;
    private $cachedResourceManager;
    private $taskService;

    public function __construct(
        QueueService $resqueQueueService,
        CachedResourceManager $cachedResourceManager,
        TaskService $taskService
    ) {
        $this->resqueQueueService = $resqueQueueService;
        $this->cachedResourceManager = $cachedResourceManager;
        $this->taskService = $taskService;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        $task = $taskEvent->getTask();

        $this->resqueQueueService->enqueue(new TaskReportCompletionJob(['id' => $task->getId()]));

        $sources = $task->getSources();
        $primarySource = $sources[$task->getUrl()] ?? null;

        if ($primarySource && $primarySource->isCachedResource()) {
            $cachedResource = $this->cachedResourceManager->find($primarySource->getValue());

            if ($cachedResource) {
                $requestHash = $cachedResource->getRequestHash();

                if (!$this->taskService->isCachedResourceRequestHashInUse($task->getId(), $requestHash)) {
                    $this->cachedResourceManager->remove($cachedResource);
                }
            }
        }
    }
}
