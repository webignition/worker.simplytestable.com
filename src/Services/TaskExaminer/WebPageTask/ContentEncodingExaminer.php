<?php

namespace App\Services\TaskExaminer\WebPageTask;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Services\TaskOutputMessageFactory;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\WebPage\ContentEncodingValidator;

class ContentEncodingExaminer
{
    private $taskCachedSourceWebPageRetriever;
    private $taskOutputMessageFactory;

    public function __construct(
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        TaskOutputMessageFactory $taskOutputMessageFactory
    ) {
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;
        $this->taskOutputMessageFactory = $taskOutputMessageFactory;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        $this->examine($taskEvent->getTask());
    }

    public function examine(Task $task)
    {
        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);

        if (empty($webPage)) {
            return;
        }

        $contentEncodingValidator = new ContentEncodingValidator();
        if (!$contentEncodingValidator->isValid($webPage)) {
            $task->setState(Task::STATE_FAILED_NO_RETRY_AVAILABLE);
            $webPageCharacterSet = $webPage->getCharacterSet();

            $webPageCharacterSet = $webPageCharacterSet ?? 'utf-8';

            $outputContent = $this->taskOutputMessageFactory->createInvalidCharacterEncodingOutput(
                $webPageCharacterSet
            );

            $task->setOutput(Output::create(
                json_encode($outputContent),
                new InternetMediaType('application', 'json'),
                1
            ));
        }
    }
}
