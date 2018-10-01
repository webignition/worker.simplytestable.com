<?php

namespace App\Services\Request\Factory\Task;

use App\Model\Task\Type;
use App\Request\Task\CreateRequest;
use App\Services\TaskTypeService;
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
        $taskTypeValue = strtolower(trim($this->requestParameters->get(self::PARAMETER_TYPE)));
        $taskType = $this->taskTypeService->get($taskTypeValue);

        return new CreateRequest(
            $this->getStringValueFromRequestParameters(self::PARAMETER_URL),
            $taskType,
            $this->getStringValueFromRequestParameters(self::PARAMETER_PARAMETERS)
        );
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
