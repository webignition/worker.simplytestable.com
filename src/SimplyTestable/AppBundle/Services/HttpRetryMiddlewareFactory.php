<?php

namespace SimplyTestable\AppBundle\Services;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpRetryMiddlewareFactory
{
    const MAX_RETRIES = 5;

    /**
     * @return callable
     */
    public function create()
    {
        return Middleware::retry($this->createRetryDecider());
    }

    /**
     * @return \Closure
     */
    private function createRetryDecider()
    {
        return function (
            $retries,
            RequestInterface $request,
            ResponseInterface $response = null,
            GuzzleException $exception = null
        ) {
            if ($retries >= self::MAX_RETRIES) {
                return false;
            }

            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response instanceof ResponseInterface && $response->getStatusCode() >= 500) {
                return true;
            }

            return false;
        };
    }
}
