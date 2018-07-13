<?php

namespace SimplyTestable\AppBundle\Model\Task;

use GuzzleHttp\Cookie\SetCookie;
use SimplyTestable\AppBundle\Entity\Task\Task;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;
use webignition\NormalisedUrl\NormalisedUrl;

class Parameters
{
    const PARAMETER_KEY_COOKIES = 'cookies';
    const PARAMETER_HTTP_AUTH_USERNAME = 'http-auth-username';
    const PARAMETER_HTTP_AUTH_PASSWORD = 'http-auth-password';

    /**
     * @var Task
     */
    private $task;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @param Task $task
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->parameters = $task->getParametersArray();
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

        $taskUrl = new NormalisedUrl($this->task->getUrl());

        return new HttpAuthenticationCredentials($username, $password, $taskUrl->getHost());
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
