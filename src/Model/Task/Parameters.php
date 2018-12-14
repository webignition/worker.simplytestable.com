<?php

namespace App\Model\Task;

use GuzzleHttp\Cookie\SetCookie;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;
use webignition\NormalisedUrl\NormalisedUrl;

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

    /**
     * @return HttpAuthenticationCredentials
     */
    public function getHttpAuthenticationCredentials()
    {
        if (!$this->has(self::PARAMETER_HTTP_AUTH_USERNAME)) {
            return new HttpAuthenticationCredentials();
        }

        $username = $this->parameters[self::PARAMETER_HTTP_AUTH_USERNAME];
        $password = $this->has(self::PARAMETER_HTTP_AUTH_PASSWORD)
            ? $this->parameters[self::PARAMETER_HTTP_AUTH_PASSWORD]
            : null;

        $taskUrl = new NormalisedUrl($this->taskUrl);

        return new HttpAuthenticationCredentials($username, $password, $taskUrl->getHost());
    }

    public function get(string $key)
    {
        return $this->parameters[$key] ?? null;
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
