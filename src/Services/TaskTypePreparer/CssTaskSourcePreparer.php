<?php

namespace App\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Task\Type;
use App\Services\TaskSourceRetriever;
use App\Services\WebPageTaskCssUrlFinder;
use webignition\WebResource\Retriever as WebResourceRetriever;

class CssTaskSourcePreparer
{
    private $taskSourceRetriever;
    private $webResourceRetriever;
    private $webPageTaskCssUrlFinder;

    public function __construct(
        TaskSourceRetriever $taskSourceRetriever,
        WebResourceRetriever $webResourceRetriever,
        WebPageTaskCssUrlFinder $webPageTaskCssUrlFinder
    ) {
        $this->taskSourceRetriever = $taskSourceRetriever;
        $this->webResourceRetriever = $webResourceRetriever;
        $this->webPageTaskCssUrlFinder = $webPageTaskCssUrlFinder;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        if (Type::TYPE_CSS_VALIDATION === (string) $taskEvent->getTask()->getType()) {
            $preparationIsComplete = $this->prepare($taskEvent->getTask());

            if (false === $preparationIsComplete) {
                $taskEvent->stopPropagation();
            }
        }
    }

    public function prepare(Task $task)
    {
        if (Type::TYPE_CSS_VALIDATION !== (string) $task->getType()) {
            return null;
        }

        $stylesheetUrls = $this->webPageTaskCssUrlFinder->find($task);

        $nextUnSourcedStylesheetUrl = $this->findNextUnSourcedStylesheetUrl($stylesheetUrls, $task->getSources());
        if (null === $nextUnSourcedStylesheetUrl) {
            return true;
        }

        $this->taskSourceRetriever->retrieve($this->webResourceRetriever, $task, $nextUnSourcedStylesheetUrl);
        $stylesheetUrls = $this->webPageTaskCssUrlFinder->find($task);

        return null === $this->findNextUnSourcedStylesheetUrl($stylesheetUrls, $task->getSources());
    }

    private function findNextUnSourcedStylesheetUrl(array $stylesheetUrls, array $sources): ?string
    {
        $nextUnSourcedStylesheetUrl = null;

        foreach ($stylesheetUrls as $stylesheetUrl) {
            $hasSource = array_key_exists($stylesheetUrl, $sources);

            if (!$hasSource && null === $nextUnSourcedStylesheetUrl) {
                $nextUnSourcedStylesheetUrl = $stylesheetUrl;
            }
        }

        return $nextUnSourcedStylesheetUrl;
    }
}
