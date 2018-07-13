<?php

namespace AppBundle\Services\Request\Factory\Task;

use AppBundle\Entity\Task\Type\Type as TaskType;
use AppBundle\Request\Task\CreateRequest;
use AppBundle\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

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
     * @param RequestStack $requestStack
     * @param TaskTypeService $taskTypeService
     */
    public function __construct(RequestStack $requestStack, TaskTypeService $taskTypeService)
    {
        $this->requestParameters = $requestStack->getCurrentRequest()->request;
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
            $this->getStringValueFromRequestParameters(self::PARAMETER_URL),
            $this->getStringValueFromRequestParameters(self::PARAMETER_PARAMETERS)
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
     * @param string $key
     *
     * @return string
     */
    private function getStringValueFromRequestParameters($key)
    {
        return trim($this->requestParameters->get($key));
    }
}
