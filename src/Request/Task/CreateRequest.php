<?php

namespace App\Request\Task;

use App\Entity\Task\Type\Type as TaskType;

class CreateRequest
{
    /**
     * @var TaskType
     */
    private $taskType;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $parameters;

    /**
     * @param TaskType $taskType
     * @param string $url
     * @param string $parameters
     */
    public function __construct($taskType, $url, $parameters)
    {
        $this->taskType = $taskType;
        $this->url = trim($url);
        $this->parameters = trim($parameters);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if (!$this->taskType instanceof TaskType) {
            return false;
        }

        if (empty($this->url)) {
            return false;
        }

        return true;
    }

    /**
     * @return TaskType
     */
    public function getTaskType()
    {
        return $this->taskType;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}
