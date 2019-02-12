<?php

namespace App\Services;

use App\Entity\Task\Task;

class TaskUnusedCachedResourceRemover
{
    private $cachedResourceManager;
    private $taskService;

    public function __construct(CachedResourceManager $cachedResourceManager, TaskService $taskService)
    {
        $this->cachedResourceManager = $cachedResourceManager;
        $this->taskService = $taskService;
    }

    public function remove(Task $task)
    {
        $sources = $task->getSources();

        foreach ($sources as $source) {
            if ($source->isCachedResource()) {
                $cachedResource = $this->cachedResourceManager->find($source->getValue());

                if ($cachedResource) {
                    $requestHash = $cachedResource->getRequestHash();

                    if (!$this->taskService->isCachedResourceRequestHashInUse($task->getId(), $requestHash)) {
                        $this->cachedResourceManager->remove($cachedResource);
                    }
                }
            }
        }
    }
}
