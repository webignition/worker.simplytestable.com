<?php

namespace App\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
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

    public function __invoke(TaskEvent $taskEvent)
    {
        $this->prepare($taskEvent->getTask());
    }

    public function prepare(Task $task)
    {
        $taskUrl = $task->getUrl();

        $existingSources = $task->getSources();
        if (array_key_exists($taskUrl, $existingSources)) {
            return;
        }

        $this->taskSourceRetriever->retrieve($this->webResourceRetriever, $task, $taskUrl);
    }
}
