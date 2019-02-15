<?php

namespace App\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\CssSourceUrl;
use App\Model\Source;
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

        $cssSourceUrls = $this->webPageTaskCssUrlFinder->find($task);

        $nextUnSourcedCssSourceUrl = $this->findNextUnSourcedStylesheetUrl($cssSourceUrls, $task->getSources());
        if (null === $nextUnSourcedCssSourceUrl) {
            return true;
        }

        $this->taskSourceRetriever->retrieve(
            $this->webResourceRetriever,
            $task,
            $nextUnSourcedCssSourceUrl->getUrl(),
            [
                'origin' => $nextUnSourcedCssSourceUrl->getType()
            ]
        );
        $cssSourceUrls = $this->webPageTaskCssUrlFinder->find($task);

        return null === $this->findNextUnSourcedStylesheetUrl($cssSourceUrls, $task->getSources());
    }

    /**
     * @param CssSourceUrl[] $cssSourceUrls
     * @param Source[] $sources
     *
     * @return CssSourceUrl|null
     */
    private function findNextUnSourcedStylesheetUrl(array $cssSourceUrls, array $sources): ?CssSourceUrl
    {
        $nextUnsourcedCssSourceUrl = null;

        foreach ($cssSourceUrls as $cssSourceUrl) {
            $url = $cssSourceUrl->getUrl();

            $hasSource = array_key_exists($url, $sources);

            if (!$hasSource && null === $nextUnsourcedCssSourceUrl) {
                $nextUnsourcedCssSourceUrl = $cssSourceUrl;
            }
        }

        return $nextUnsourcedCssSourceUrl;
    }
}
