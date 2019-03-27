<?php

namespace App\Services\Request\Factory\Task;

use App\Request\Task\CreateRequest;
use App\Services\Request\Factory\AbstractPostRequestFactory;
use App\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateRequestFactory extends AbstractPostRequestFactory
{
    const PARAMETER_TYPE = 'type';
    const PARAMETER_URL = 'url';
    const PARAMETER_PARAMETERS = 'parameters';

    private $taskTypeService;

    public function __construct(RequestStack $requestStack, TaskTypeService $taskTypeService)
    {
        parent::__construct($requestStack);

        $this->taskTypeService = $taskTypeService;
    }

    public function setRequestParameters(ParameterBag $parameters)
    {
        $this->requestParameters = $parameters;
    }

    public function create(): ?CreateRequest
    {
        $taskTypeValue = strtolower(trim($this->requestParameters->get(self::PARAMETER_TYPE)));
        $taskType = $this->taskTypeService->get($taskTypeValue);

        if (empty($taskType) || ($taskType && !$taskType->isSelectable())) {
            return null;
        }

        return new CreateRequest(
            trim($this->requestParameters->get(self::PARAMETER_URL)),
            $taskType,
            trim($this->requestParameters->get(self::PARAMETER_PARAMETERS))
        );
    }
}
