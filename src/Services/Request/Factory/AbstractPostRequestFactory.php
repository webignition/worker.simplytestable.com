<?php

namespace App\Services\Request\Factory;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractPostRequestFactory
{
    const PARAMETER_HOSTNAME = 'hostname';
    const PARAMETER_TOKEN = 'token';

    /**
     * @var ParameterBag
     */
    protected $requestParameters;

    public function __construct(RequestStack $requestStack)
    {
        $currentRequest = $requestStack->getCurrentRequest();
        $this->requestParameters = $currentRequest
            ? $currentRequest->request
            : new ParameterBag();
    }
}
