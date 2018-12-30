<?php

namespace App\EventListener;

use App\Event\TaskEvent;
use App\Resque\Job\TaskReportCompletionJob;
use App\Services\CachedResourceManager;
use App\Services\Resque\QueueService;

class TaskPerformedEventListener
{
    private $resqueQueueService;
    private $cachedResourceManager;

    public function __construct(
        QueueService $resqueQueueService,
        CachedResourceManager $cachedResourceManager
    ) {
        $this->resqueQueueService = $resqueQueueService;
        $this->cachedResourceManager = $cachedResourceManager;
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
                $this->cachedResourceManager->remove($cachedResource);
            }
        }
    }
}
