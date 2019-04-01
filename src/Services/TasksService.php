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
    public function request(?int $requestedLimit = null): bool
    {
        $isWithinThreshold = $this->taskService->getInCompleteCount() <= $this->workerProcessCount;
        if (!$isWithinThreshold) {
            return false;
        }

        $request = $this->coreApplicationHttpClient->createPostRequest(
            'tasks_request',
            [],
            [
                'worker_hostname' => $this->applicationConfiguration->getHostname(),
                'worker_token' => $this->applicationConfiguration->getToken(),
                'limit' => $this->calculateLimit($requestedLimit)
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

    private function calculateLimit(?int $requestedLimit = null): int
    {
        $upperLimit = (int) round($this->workerProcessCount * $this->maxTasksRequestFactor);

        $calculatedLimit = $upperLimit - $this->taskService->getInCompleteCount();
        if (is_null($requestedLimit)) {
            return $calculatedLimit;
        }

        if ($requestedLimit < 1) {
            $requestedLimit = 1;
        }

        return (int) min($requestedLimit, $calculatedLimit);
    }

    private function createRequestException(HttpRequestException $requestException): RequestException
    {
        $exceptionCode = 0;

        if ($requestException instanceof ConnectException) {
            $curlException = GuzzleCurlExceptionFactory::fromConnectException($requestException);

            if ($curlException) {
                $exceptionCode = $curlException->getCurlCode();
            }
        } else {
            $response = $requestException->getResponse();

            if ($response) {
                $exceptionCode = $response->getStatusCode();
            }
        }

        return new RequestException(
            get_class($requestException),
            $exceptionCode,
            $requestException
        );
    }

    private function logHttpRequestException(RequestException $requestException)
    {
        $previousException = $requestException->getPrevious();
        $previousExceptionClass = $previousException
            ? get_class($previousException)
            : 'no previous exception';

        $this->logger->error(sprintf(
            'TasksService:request:%s [%s]',
            $previousExceptionClass,
            $requestException->getCode()
        ));
    }
}
