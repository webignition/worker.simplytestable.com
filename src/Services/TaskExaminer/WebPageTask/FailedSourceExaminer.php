<?php

namespace App\Services\TaskExaminer\WebPageTask;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Source;
use App\Services\TaskOutputMessageFactory;
use webignition\InternetMediaType\InternetMediaType;

class FailedSourceExaminer
{
    private $taskOutputMessageFactory;

    public function __construct(TaskOutputMessageFactory $taskOutputMessageFactory)
    {
        $this->taskOutputMessageFactory = $taskOutputMessageFactory;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        $propagationCanContinue = $this->examine($taskEvent->getTask());

        if (false === $propagationCanContinue) {
            $taskEvent->stopPropagation();
        }
    }

    public function examine(Task $task): bool
    {
        if (!$task->isIncomplete()) {
            return false;
        }

        $sources = $task->getSources();
        /* @var Source $primarySource */
        $primarySource = $sources[$task->getUrl()] ?? null;

        if (empty($primarySource)) {
            return false;
        }

        if ($primarySource->isUnavailable()) {
            $task->setState(Task::STATE_FAILED_NO_RETRY_AVAILABLE);

            $outputContent = $this->taskOutputMessageFactory->createOutputMessageCollectionFromSource($primarySource);

            $task->setOutput(Output::create(
                json_encode($outputContent),
                new InternetMediaType('application', 'json'),
                1
            ));

            return false;
        }

        return true;
    }
}
