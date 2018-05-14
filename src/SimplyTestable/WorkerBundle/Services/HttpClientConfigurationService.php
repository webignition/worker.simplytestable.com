<?php

namespace SimplyTestable\WorkerBundle\Services;

use SimplyTestable\WorkerBundle\Entity\Task\Task;

class HttpClientConfigurationService
{
    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @param HttpClientService $httpClientService
     */
    public function __construct(HttpClientService $httpClientService)
    {
        $this->httpClientService = $httpClientService;
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
            $this->httpClientService->setCookies($parametersObject->getCookies());
        }

        $httpAuthenticationCredentials = $parametersObject->getHttpAuthenticationCredentials();
        if (!$httpAuthenticationCredentials->isEmpty()) {
            $this->httpClientService->setBasicHttpAuthorization($httpAuthenticationCredentials);
        }

        $this->httpClientService->setRequestHeader('User-Agent', $userAgentString);
    }
}
