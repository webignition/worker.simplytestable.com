<?php

namespace AppBundle\Services\Request\Factory;

use AppBundle\Request\VerifyRequest;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

class VerifyRequestFactory
{
    const PARAMETER_HOSTNAME = 'hostname';
    const PARAMETER_TOKEN = 'token';

    /**
     * @var ParameterBag
     */
    private $requestParameters;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestParameters = $requestStack->getCurrentRequest()->request;
    }

    /**
     * @return VerifyRequest
     */
    public function create()
    {
        return new VerifyRequest(
            $this->getStringValueFromRequestParameters(self::PARAMETER_HOSTNAME),
            $this->getStringValueFromRequestParameters(self::PARAMETER_TOKEN)
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
