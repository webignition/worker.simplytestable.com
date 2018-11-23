<?php

namespace App\Event;

use App\Entity\Task\Task;
use Symfony\Component\EventDispatcher\Event;

class TaskEvent extends Event
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
