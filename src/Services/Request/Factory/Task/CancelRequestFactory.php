<?php

namespace App\Services\Request\Factory\Task;

use App\Entity\Task\Task;
use App\Request\Task\CancelRequest;
use App\Services\Request\Factory\AbstractPostRequestFactory;
use App\Services\TaskService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class CancelRequestFactory extends AbstractPostRequestFactory
{
    const PARAMETER_ID = 'id';

    private $taskService;

    public function __construct(RequestStack $requestStack, TaskService $taskService)
    {
        parent::__construct($requestStack);

        $this->taskService = $taskService;
    }

    public function setRequestParameters(ParameterBag $parameters)
    {
        $this->requestParameters = $parameters;
    }

    public function create(): ?CancelRequest
    {
        $task = $this->getTaskFromRequestParameters();

        return $task ? new CancelRequest($task) : null;
    }

    private function getTaskFromRequestParameters(): ?Task
    {
        if (!$this->requestParameters->has(self::PARAMETER_ID)) {
            return null;
        }

        $requestTaskId = trim($this->requestParameters->get(self::PARAMETER_ID));
        if (empty($requestTaskId) || !ctype_digit($requestTaskId)) {
            return null;
        }

        return $this->taskService->getById((int) $requestTaskId);
    }
}
