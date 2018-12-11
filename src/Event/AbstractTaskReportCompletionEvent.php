<?php

namespace App\Event;

use App\Entity\Task\Task;

abstract class AbstractTaskReportCompletionEvent extends TaskEvent
{
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_FAILED = 'failed';

    private $status;

    public function __construct(Task $task, string $status)
    {
        parent::__construct($task);

        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
