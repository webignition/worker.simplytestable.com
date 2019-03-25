<?php

namespace App\Model\Task;

use GuzzleHttp\Cookie\SetCookie;

class Parameters
{
    const PARAMETER_KEY_COOKIES = 'cookies';
    const PARAMETER_HTTP_AUTH_USERNAME = 'http-auth-username';
    const PARAMETER_HTTP_AUTH_PASSWORD = 'http-auth-password';

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var string
     */
    private $taskUrl;

    public function __construct(array $parameters, string $taskUrl)
    {
        $this->parameters = $parameters;
        $this->taskUrl = $taskUrl;
    }

    /**
     * @return SetCookie[]
     */
    public function getCookies()
    {
        $cookies = [];

        if (!$this->has(self::PARAMETER_KEY_COOKIES)) {
            return $cookies;
        }

        $cookieValuesCollection = $this->parameters[self::PARAMETER_KEY_COOKIES];

        foreach ($cookieValuesCollection as $cookieValues) {
            foreach ($cookieValues as $key => $value) {
                $normalisedKey = ucfirst(strtolower($key));

                unset($cookieValues[$key]);
                $cookieValues[$normalisedKey] = $value;
            }

            $cookies[] = new SetCookie($cookieValues);
        }

        return $cookies;
    }

    public function getHttpAuthenticationUsername(): ?string
    {
        return $this->parameters[self::PARAMETER_HTTP_AUTH_USERNAME] ?? '';
    }

    public function getHttpAuthenticationPassword(): ?string
    {
        return $this->parameters[self::PARAMETER_HTTP_AUTH_PASSWORD] ?? '';
    }

    public function get(string $key)
    {
        return $this->parameters[$key] ?? null;
    }

    public function toArray(): array
    {
        return $this->parameters;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    private function has($key)
    {
        return array_key_exists($key, $this->parameters);
    }
}
