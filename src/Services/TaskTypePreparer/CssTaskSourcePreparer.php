<?php

namespace App\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Task\Type;
use App\Services\CssSourceInspector;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Services\TaskSourceRetriever;
use webignition\WebResource\Retriever as WebResourceRetriever;

class CssTaskSourcePreparer
{
    private $taskCachedSourceWebPageRetriever;
    private $taskSourceRetriever;
    private $webResourceRetriever;
    private $cssSourceInspector;

    public function __construct(
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        TaskSourceRetriever $taskSourceRetriever,
        WebResourceRetriever $webResourceRetriever,
        CssSourceInspector $cssSourceInspector
    ) {
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;
        $this->taskSourceRetriever = $taskSourceRetriever;
        $this->webResourceRetriever = $webResourceRetriever;
        $this->cssSourceInspector = $cssSourceInspector;
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

        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);

        $stylesheetUrls = $this->cssSourceInspector->findStylesheetUrls($webPage);

        $nextUnSourcedStylesheetUrl = $this->findNextUnSourcedStylesheetUrl($stylesheetUrls, $task->getSources());
        if (null === $nextUnSourcedStylesheetUrl) {
            return true;
        }

        $this->taskSourceRetriever->retrieve($this->webResourceRetriever, $task, $nextUnSourcedStylesheetUrl);

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
