<?php

namespace App\Services;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\RequestException as HttpRequestException;
use App\Exception\Services\TasksService\RequestException;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class TasksService
{
    private $logger;
    private $applicationConfiguration;
    private $taskService;
    private $coreApplicationHttpClient;
    private $workerProcessCount = 1;
    private $maxTasksRequestFactor = 1;

    public function __construct(
        LoggerInterface $logger,
        ApplicationConfiguration $applicationConfiguration,
        TaskService $taskService,
        CoreApplicationHttpClient $coreApplicationHttpClient,
        int $workerProcessCount,
        int $maxTasksRequestFactor
    ) {
        $this->logger = $logger;
        $this->applicationConfiguration = $applicationConfiguration;
        $this->taskService = $taskService;
        $this->coreApplicationHttpClient = $coreApplicationHttpClient;
        $this->workerProcessCount = $workerProcessCount;
        $this->maxTasksRequestFactor = $maxTasksRequestFactor;
    }

    public function getWorkerProcessCount(): int
    {
        return $this->workerProcessCount;
    }

    /**
     * @return int
     */
    public function getMaxTasksRequestFactor(): int
    {
        return $this->maxTasksRequestFactor;
    }

    /**
     * @param null|int $requestedLimit
     *
     * @return bool
     *
     * @throws RequestException
     * @throws GuzzleException
     */
    public function request($requestedLimit = null)
    {
        if (!$this->isWithinThreshold()) {
            return false;
        }

        $request = $this->coreApplicationHttpClient->createPostRequest(
            'tasks_request',
            [],
            [
                'worker_hostname' => $this->applicationConfiguration->getHostname(),
                'worker_token' => $this->applicationConfiguration->getToken(),
                'limit' => $this->getLimit($requestedLimit)
            ]
        );

        try {
            $this->coreApplicationHttpClient->send($request);
        } catch (HttpRequestException $httpRequestException) {
            $requestException = $this->createRequestException($httpRequestException);
            $this->logHttpRequestException($requestException);

            throw $requestException;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function isWithinThreshold()
    {
        return $this->taskService->getInCompleteCount() <= $this->getLowerLimit();
    }

    /**
     * @return int
     */
    private function getUpperLimit()
    {
        return (int)round($this->workerProcessCount * $this->getMaxTasksRequestFactor());
    }

    /**
     * @return int
     */
    private function getLowerLimit()
    {
        return $this->workerProcessCount;
    }

    /**
     * @param int $requestedLimit
     *
     * @return int
     */
    private function getLimit($requestedLimit = null)
    {
        $calculatedLimit = $this->getUpperLimit() - $this->taskService->getInCompleteCount();
        if (is_null($requestedLimit)) {
            return $calculatedLimit;
        }

        if ($requestedLimit < 1) {
            $requestedLimit = 1;
        }

        return min($requestedLimit, $calculatedLimit);
    }


    /**
     * @param HttpRequestException $requestException
     *
     * @return RequestException
     */
    private function createRequestException(HttpRequestException $requestException)
    {
        $exceptionCode = null;

        if ($requestException instanceof ConnectException &&
            GuzzleCurlExceptionFactory::isCurlException($requestException)
        ) {
            $curlException = GuzzleCurlExceptionFactory::fromConnectException($requestException);
            $exceptionCode = $curlException->getCurlCode();
        } else {
            $exceptionCode = $requestException->getResponse()->getStatusCode();
        }

        return new RequestException(
            get_class($requestException),
            $exceptionCode,
            $requestException
        );
    }

    /**
     * @param RequestException $requestException
     */
    private function logHttpRequestException(RequestException $requestException)
    {
        $this->logger->error(sprintf(
            'TasksService:request:%s [%s]',
            get_class($requestException->getPrevious()),
            $requestException->getCode()
        ));
    }
}
