<?php

namespace App\Request\Task;

use App\Entity\Task\Task;

class CancelRequest
{
    /**
     * @var Task
     */
    private $task;

    /**
     * @param Task $task
     */
    public function __construct($task)
    {
        $this->task = $task;
    }

    /**
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->task instanceof Task;
    }
}
