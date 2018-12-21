<?php

namespace App\Model;

use webignition\WebResource\WebPage\WebPage;

class TaskPerformerWebPageRetrieverResult
{
    /**
     * @var WebPage
     */
    private $webPage;

    /**
     * @var string
     */
    private $taskState;

    /**
     * @var TaskOutputValues
     */
    private $taskOutputValues;

    public function setWebPage(WebPage $webPage)
    {
        $this->webPage = $webPage;
    }

    public function getWebPage(): ?WebPage
    {
        return $this->webPage;
    }

    public function setTaskState(string $state)
    {
        $this->taskState = $state;
    }

    public function getTaskState(): string
    {
        return $this->taskState;
    }

    public function setTaskOutputValues(TaskOutputValues $taskOutputValues)
    {
        $this->taskOutputValues = $taskOutputValues;
    }

    public function getTaskOutputValues(): ?TaskOutputValues
    {
        return $this->taskOutputValues;
    }
}
