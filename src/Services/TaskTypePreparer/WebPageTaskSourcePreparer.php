<?php

namespace App\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Exception\UnableToRetrieveResourceException;
use App\Services\TaskSourceRetriever;
use webignition\WebResource\Retriever as WebResourceRetriever;

class WebPageTaskSourcePreparer
{
    private $taskSourceRetriever;
    private $webResourceRetriever;

    public function __construct(TaskSourceRetriever $taskSourceRetriever, WebResourceRetriever $webResourceRetriever)
    {
        $this->taskSourceRetriever = $taskSourceRetriever;
        $this->webResourceRetriever = $webResourceRetriever;
    }

    /**
     * @param TaskEvent $taskEvent
     *
     * @throws UnableToRetrieveResourceException
     */
    public function __invoke(TaskEvent $taskEvent)
    {
        $this->prepare($taskEvent->getTask());
    }

    /**
     * @param Task $task
     *
     * @throws UnableToRetrieveResourceException
     */
    public function prepare(Task $task)
    {
        $taskUrl = $task->getUrl();

        $existingSources = $task->getSources();
        if (array_key_exists($taskUrl, $existingSources)) {
            return;
        }

        $retrieveResult = $this->taskSourceRetriever->retrieve($this->webResourceRetriever, $task, $taskUrl);

        if (false === $retrieveResult) {
            throw new UnableToRetrieveResourceException();
        }
    }
}
