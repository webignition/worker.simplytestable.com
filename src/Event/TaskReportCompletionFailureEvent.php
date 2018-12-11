<?php

namespace App\Event;

use App\Entity\Task\Task;

class TaskReportCompletionFailureEvent extends AbstractTaskReportCompletionEvent
{
    const SUCCEEDED = false;

    const FAILURE_TYPE_HTTP = 'http';
    const FAILURE_TYPE_CURL = 'curl';
    const FAILURE_TYPE_UNKNOWN = 'unknown';

    private $failureType;
    private $statusCode;
    private $requestUrl;

    public function __construct(Task $task, string $failureType, int $statusCode, string $requestUrl)
    {
        parent::__construct($task, self::SUCCEEDED);

        $this->failureType = $failureType;
        $this->statusCode = $statusCode;
        $this->requestUrl = $requestUrl;
    }

    public function getFailureType(): ?string
    {
        return $this->failureType;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }
}
