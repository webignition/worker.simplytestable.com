<?php

namespace App\Event;

use App\Entity\Task\Task;

class TaskReportCompletionSuccessEvent extends AbstractTaskReportCompletionEvent
{
    const SUCCEEDED = true;

    public function __construct(Task $task)
    {
        parent::__construct($task, self::SUCCEEDED);
    }
}
