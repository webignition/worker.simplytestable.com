<?php

namespace App\Services\TaskTypePerformer\WebPageTask;

use App\Entity\CachedResource;
use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\CachedResourceManager;
use App\Services\TaskTypePerformer\TaskPerformerInterface;
use webignition\InternetMediaType\InternetMediaType;

class InvalidSourceExaminer implements TaskPerformerInterface
{
    private $cachedResourceManager;
    private $priority;

    public function __construct(CachedResourceManager $cachedResourceManager, int $priority)
    {
        $this->cachedResourceManager = $cachedResourceManager;
        $this->priority = $priority;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        $this->perform($taskEvent->getTask());
    }

    public function perform(Task $task)
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

    public function handles(string $taskType): bool
    {
        return in_array($taskType, [
            TypeInterface::TYPE_HTML_VALIDATION,
            TypeInterface::TYPE_CSS_VALIDATION,
            TypeInterface::TYPE_LINK_INTEGRITY,
            TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL,
            TypeInterface::TYPE_URL_DISCOVERY,
        ]);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
