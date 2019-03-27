<?php

namespace App\Request\Task;

use App\Entity\Task\Task;

class CancelRequest
{
    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function getTask(): Task
    {
        return $this->task;
    }
}
