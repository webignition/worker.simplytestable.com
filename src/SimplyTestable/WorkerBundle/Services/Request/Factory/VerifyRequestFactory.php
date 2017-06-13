<?php

namespace SimplyTestable\WorkerBundle\Services\Request\Factory;

use SimplyTestable\WorkerBundle\Request\VerifyRequest;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class VerifyRequestFactory
{
    const PARAMETER_HOSTNAME = 'hostname';
    const PARAMETER_TOKEN = 'token';

    /**
     * @var ParameterBag
     */
    private $requestParameters;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->requestParameters = $request->request;
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
