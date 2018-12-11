<?php

namespace App\Event;

use App\Entity\Task\Task;

abstract class AbstractTaskReportCompletionEvent extends TaskEvent
{
    private $succeeded;

    public function __construct(Task $task, bool $succeeded)
    {
        parent::__construct($task);

        $this->succeeded = $succeeded;
    }

    public function isSucceeded(): bool
    {
        return $this->succeeded;
    }
}
