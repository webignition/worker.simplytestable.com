<?php

namespace App\Event;

use App\Entity\Task\Task;
use Symfony\Component\EventDispatcher\Event;

class TaskEvent extends Event
{
    const TYPE_CREATED = 'task.created';
    const TYPE_PREPARED = 'task.prepared';
    const TYPE_PERFORMED = 'task.performed';
    const TYPE_REPORTED_COMPLETION = 'task.reported-completion';

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
