<?php

namespace SimplyTestable\WorkerBundle\Services\GuzzleMiddleware;

use Psr\Http\Message\RequestInterface;
use SimplyTestable\WorkerBundle\Model\HttpAuthenticationCredentials;
use SimplyTestable\WorkerBundle\Model\HttpAuthenticationHeader;

class HttpAuthenticationMiddleware
{
    /**
     * @var HttpAuthenticationCredentials
     */
    private $httpAuthenticationCredentials;

    public function __construct()
    {
        $this->httpAuthenticationCredentials = new HttpAuthenticationCredentials();
    }

    /**
     * @param HttpAuthenticationCredentials $httpAuthenticationCredentials
     */
    public function setHttpAuthenticationCredentials(HttpAuthenticationCredentials $httpAuthenticationCredentials)
    {
        $this->httpAuthenticationCredentials = $httpAuthenticationCredentials;
    }

    /**
     * @param callable $handler
     *
     * @return callable
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use (&$handler) {
            if ($this->httpAuthenticationCredentials->isEmpty()) {
                return $handler($request, $options);
            }

            $httpAuthenicationHeader = new HttpAuthenticationHeader($this->httpAuthenticationCredentials);

            if (!$httpAuthenicationHeader->isValidForRequest($request)) {
                return $handler($request, $options);
            }

            return $handler(
                $request->withHeader(
                    $httpAuthenicationHeader->getName(),
                    $httpAuthenicationHeader->getValue()
                ),
                $options
            );
        };
    }
}
