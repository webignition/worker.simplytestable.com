<?php

namespace App\Services;

use GuzzleHttp\Cookie\CookieJarInterface;
use App\Entity\Task\Task;
use webignition\Guzzle\Middleware\HttpAuthentication\AuthorizationType;
use webignition\Guzzle\Middleware\HttpAuthentication\CredentialsFactory;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\Guzzle\Middleware\RequestHeaders\RequestHeadersMiddleware;
use webignition\Uri\Uri;

class HttpClientConfigurationService
{
    private $httpAuthenticationMiddleware;
    private $requestHeadersMiddleware;
    private $cookieJar;

    /**
     * @param HttpAuthenticationMiddleware $httpAuthenticationMiddleware
     * @param RequestHeadersMiddleware $requestHeadersMiddleware
     * @param CookieJarInterface $cookieJar
     */
    public function __construct(
        HttpAuthenticationMiddleware $httpAuthenticationMiddleware,
        RequestHeadersMiddleware $requestHeadersMiddleware,
        CookieJarInterface $cookieJar
    ) {
        $this->httpAuthenticationMiddleware = $httpAuthenticationMiddleware;
        $this->requestHeadersMiddleware = $requestHeadersMiddleware;
        $this->cookieJar = $cookieJar;
    }

    /**
     * @param Task $task
     * @param $userAgentString
     */
    public function configureForTask(Task $task, $userAgentString)
    {
        $parametersObject = $task->getParameters();

        $cookies = $parametersObject->getCookies();
        if (!empty($cookies)) {
            $this->cookieJar->clear();

            foreach ($cookies as $cookie) {
                $this->cookieJar->setCookie($cookie);
            }
        }

        $httpAuthenticationUsername = $parametersObject->getHttpAuthenticationUsername();
        if (!empty($httpAuthenticationUsername)) {
            $taskUri = new Uri($task->getUrl());

            $this->httpAuthenticationMiddleware->setType(AuthorizationType::BASIC);
            $this->httpAuthenticationMiddleware->setHost($taskUri->getHost());
            $this->httpAuthenticationMiddleware->setCredentials(
                CredentialsFactory::createBasicCredentials(
                    $httpAuthenticationUsername,
                    $parametersObject->getHttpAuthenticationPassword()
                )
            );
        }

        $this->requestHeadersMiddleware->setHeader('User-Agent', $userAgentString);
    }
}
