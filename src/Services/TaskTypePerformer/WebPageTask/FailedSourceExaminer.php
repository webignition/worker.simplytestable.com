<?php

namespace App\Services\TaskTypePerformer\WebPageTask;

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
}
