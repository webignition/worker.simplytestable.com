<?php

namespace App\Services\TaskExaminer\WebPageTask;

use App\Entity\CachedResource;
use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Source;
use App\Services\CachedResourceManager;
use webignition\InternetMediaType\InternetMediaType;

class InvalidSourceExaminer
{
    private $cachedResourceManager;

    public function __construct(CachedResourceManager $cachedResourceManager)
    {
        $this->cachedResourceManager = $cachedResourceManager;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        $this->examine($taskEvent->getTask());
    }

    public function examine(Task $task)
    {
        $sources = $task->getSources();
        /* @var Source $primarySource */
        $primarySource = $sources[$task->getUrl()] ?? null;

        if (empty($primarySource)) {
            return;
        }

        if ($primarySource->isCachedResource()) {
            /* @var CachedResource $cachedResource */
            $cachedResource = $this->cachedResourceManager->find($primarySource->getValue());

            if ($cachedResource && '' === stream_get_contents($cachedResource->getBody())) {
                $this->setTaskAsSkipped($task);
            }
        } else {
            if ($primarySource->isInvalidContentType()) {
                $this->setTaskAsSkipped($task);
            }
        }
    }

    private function setTaskAsSkipped(Task $task)
    {
        $task->setState(Task::STATE_SKIPPED);
        $task->setOutput(Output::create('', new InternetMediaType('application', 'json')));
    }
}
