<?php

namespace SimplyTestable\WorkerBundle\Services\GuzzleMiddleware;

use Psr\Http\Message\RequestInterface;

class HttpAuthenticationMiddleware
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $domain;

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = strtolower($domain);
    }

    /**
     * @param callable $handler
     *
     * @return callable
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use (&$handler) {
            if (empty($this->username)) {
                return $handler($request, $options);
            }

            if (!$this->validateDomain($request)) {
                return $handler($request, $options);
            }

            $usernamePasswordPart = base64_encode(sprintf(
                '%s:%s',
                $this->username,
                $this->password
            ));

            return $handler(
                $request->withAddedHeader(
                    'Authorization',
                    'Basic ' . $usernamePasswordPart
                ),
                $options
            );
        };
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool
     */
    private function validateDomain(RequestInterface $request)
    {
        $requestHost = $request->getHeaderLine('host');

        if ($requestHost === $this->domain) {
            return true;
        }

        return preg_match('/' . preg_quote($this->domain, '//') . '$/', $requestHost) > 0;
    }
}
