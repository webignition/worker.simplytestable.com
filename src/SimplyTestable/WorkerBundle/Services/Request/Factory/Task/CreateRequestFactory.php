<?php

namespace SimplyTestable\WorkerBundle\Services\Request\Factory\Task;

use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\WorkerBundle\Request\Task\CreateRequest;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class CreateRequestFactory
{
    const PARAMETER_TYPE = 'type';
    const PARAMETER_URL = 'url';
    const PARAMETER_PARAMETERS = 'parameters';

    /**
     * @var ParameterBag
     */
    private $requestParameters;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @param Request $request
     * @param TaskTypeService $taskTypeService
     */
    public function __construct(Request $request, TaskTypeService $taskTypeService)
    {
        $this->requestParameters = $request->request;
        $this->taskTypeService = $taskTypeService;
    }

    /**
     * @param ParameterBag $parameters
     */
    public function setRequestParameters(ParameterBag $parameters)
    {
        $this->requestParameters = $parameters;
    }

    /**
     * @return CreateRequest
     */
    public function create()
    {
        return new CreateRequest(
            $this->getTaskTypeFromRequestParameters(),
            $this->getUrlFromRequestParameters(),
            $this->getParametersFromRequestParameters()
        );
    }

    /**
     * @return null|TaskType
     */
    private function getTaskTypeFromRequestParameters()
    {
        $requestTaskType = trim($this->requestParameters->get(self::PARAMETER_TYPE));
        if (empty($requestTaskType)) {
            return null;
        }

        return $this->taskTypeService->fetch($requestTaskType);
    }

    /**
     * @return string
     */
    private function getUrlFromRequestParameters()
    {
        return $this->getStringValueFromRequestParameters(self::PARAMETER_URL);
    }

    /**
     * @return string
     */
    private function getParametersFromRequestParameters()
    {
        return $this->getStringValueFromRequestParameters(self::PARAMETER_PARAMETERS);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function getStringValueFromRequestParameters($key)
    {
        return trim($this->requestParameters->get($key));
    }
}