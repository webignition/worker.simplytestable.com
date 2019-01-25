<?php

namespace App\Services\TaskTypePerformer\WebPageTask;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Services\TaskOutputMessageFactory;
use App\Services\TaskTypePerformer\TaskPerformerInterface;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\WebPage\ContentEncodingValidator;

class ContentEncodingExaminer implements TaskPerformerInterface
{
    private $taskCachedSourceWebPageRetriever;
    private $taskOutputMessageFactory;
    private $priority;

    public function __construct(
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        TaskOutputMessageFactory $taskOutputMessageFactory,
        int $priority
    ) {
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;
        $this->taskOutputMessageFactory = $taskOutputMessageFactory;
        $this->priority = $priority;
    }

    public function perform(Task $task)
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
