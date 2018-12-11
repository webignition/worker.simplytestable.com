<?php

namespace App\Event;

use App\Entity\Task\Task;

class TaskReportCompletionSuccessEvent extends AbstractTaskReportCompletionEvent
{
    public function __construct(Task $task)
    {
        parent::__construct($task, self::STATUS_SUCCEEDED);
    }
}
