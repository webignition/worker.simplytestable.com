<?php

namespace SimplyTestable\AppBundle\Services;

use GuzzleHttp\Cookie\CookieJarInterface;
use SimplyTestable\AppBundle\Entity\Task\Task;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\Guzzle\Middleware\RequestHeaders\RequestHeadersMiddleware;

class HttpClientConfigurationService
{
    /**
     * @var HttpAuthenticationMiddleware
     */
    private $httpAuthenticationMiddleware;

    /**
     * @var RequestHeadersMiddleware
     */
    private $requestHeadersMiddleware;

    /**
     * @var CookieJarInterface
     */
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
        $parametersObject = $task->getParametersObject();

        $cookies = $parametersObject->getCookies();
        if (!empty($cookies)) {
            $this->cookieJar->clear();

            foreach ($cookies as $cookie) {
                $this->cookieJar->setCookie($cookie);
            }
        }

        $httpAuthenticationCredentials = $parametersObject->getHttpAuthenticationCredentials();
        if (!$httpAuthenticationCredentials->isEmpty()) {
            $this->httpAuthenticationMiddleware->setHttpAuthenticationCredentials($httpAuthenticationCredentials);
        }

        $this->requestHeadersMiddleware->setHeader('User-Agent', $userAgentString);
    }
}
