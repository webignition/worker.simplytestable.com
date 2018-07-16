<?php
namespace App\Services;

use App\Request\Task\CreateRequest;

class TaskFactory
{
    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @param TaskService $taskService
     */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function createFromRequest(CreateRequest $createRequest)
    {
        return $this->taskService->create(
            $createRequest->getUrl(),
            $createRequest->getTaskType(),
            $createRequest->getParameters()
        );
    }
}
