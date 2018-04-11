<?php

namespace SimplyTestable\WorkerBundle\Services\GuzzleMiddleware;

use Psr\Http\Message\RequestInterface;

class RequestHeadersMiddleware
{
    /**
     * @var array
     */
    private $headers = [];

    /**
     * @param string $name
     * @param string $value
     */
    public function setHeader($name, $value)
    {
        if (null === $value && array_key_exists($name, $this->headers)) {
            unset($this->headers[$name]);
        } else {
            $this->headers[$name] = $value;
        }
    }

    /**
     * @param callable $handler
     *
     * @return callable
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use (&$handler) {
            if (empty($this->headers)) {
                return $handler($request, $options);
            }

            foreach ($this->headers as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            return $handler($request, $options);
        };
    }
}
