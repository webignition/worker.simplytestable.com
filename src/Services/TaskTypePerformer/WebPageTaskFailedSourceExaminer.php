<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\TaskOutputMessageFactory;
use webignition\InternetMediaType\InternetMediaType;

class WebPageTaskFailedSourceExaminer implements TaskPerformerInterface
{
    private $taskOutputMessageFactory;
    private $priority;

    public function __construct(TaskOutputMessageFactory $taskOutputMessageFactory, int $priority)
    {
        $this->taskOutputMessageFactory = $taskOutputMessageFactory;
        $this->priority = $priority;
    }

    public function perform(Task $task)
    {
        $sources = $task->getSources();
        /* @var Source $primarySource */
        $primarySource = $sources[$task->getUrl()] ?? null;

        if (empty($primarySource)) {
            return;
        }

        if ($primarySource->isUnavailable()) {
            $task->setState(Task::STATE_FAILED_NO_RETRY_AVAILABLE);

            $outputContent = $this->taskOutputMessageFactory->createOutputMessageCollectionFromSource($primarySource);

            $task->setOutput(Output::create(
                json_encode($outputContent),
                new InternetMediaType('application', 'json'),
                1
            ));
        }
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
