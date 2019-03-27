<?php

namespace App\Services\Request\Factory;

use App\Request\VerifyRequest;

class VerifyRequestFactory extends AbstractPostRequestFactory
{
    const PARAMETER_HOSTNAME = 'hostname';
    const PARAMETER_TOKEN = 'token';
    public function create(): VerifyRequest
    {
        return new VerifyRequest(
            trim($this->requestParameters->get(self::PARAMETER_HOSTNAME)),
            trim($this->requestParameters->get(self::PARAMETER_TOKEN))
        );
    }
}
