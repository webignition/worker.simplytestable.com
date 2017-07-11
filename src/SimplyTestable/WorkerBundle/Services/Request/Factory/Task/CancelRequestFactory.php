<?php

namespace SimplyTestable\WorkerBundle\Services\Request\Factory\Task;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Request\Task\CancelRequest;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class CancelRequestFactory
{
    const PARAMETER_ID = 'id';

    /**
     * @var ParameterBag
     */
    private $requestParameters;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @param RequestStack $requestStack
     * @param TaskService $taskService
     */
    public function __construct(RequestStack $requestStack, TaskService $taskService)
    {
        $this->requestParameters = $requestStack->getCurrentRequest()->request;
        $this->taskService = $taskService;
    }

    /**
     * @param ParameterBag $parameters
     */
    public function setRequestParameters(ParameterBag $parameters)
    {
        $this->requestParameters = $parameters;
    }

    /**
     * @return CancelRequest
     */
    public function create()
    {
        return new CancelRequest(
            $this->getTaskFromRequestParameters()
        );
    }

    /**
     * @return null|Task
     */
    private function getTaskFromRequestParameters()
    {
        $requestTaskId = trim($this->requestParameters->get(self::PARAMETER_ID));
        if (empty($requestTaskId) || !ctype_digit($requestTaskId)) {
            return null;
        }

        return $this->taskService->getById($requestTaskId);
    }
}
