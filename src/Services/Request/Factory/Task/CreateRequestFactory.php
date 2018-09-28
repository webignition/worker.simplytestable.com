<?php

namespace App\Services\Request\Factory\Task;

use App\Request\Task\CreateRequest;
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

    public function __construct(RequestStack $requestStack)
    {
        $this->requestParameters = $requestStack->getCurrentRequest()->request;
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

    private function getTaskTypeFromRequestParameters(): ?string
    {
        return trim($this->requestParameters->get(self::PARAMETER_TYPE));
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
