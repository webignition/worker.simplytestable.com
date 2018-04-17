<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\RequestException as HttpRequestException;
use SimplyTestable\WorkerBundle\Exception\Services\TasksService\RequestException;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class TasksService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var WorkerService $workerService
     */
    private $workerService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var int
     */
    private $workerProcessCount = null;

    /**
     * @var int
     */
    private $maxTasksRequestFactor = null;

    /**
     * @var CoreApplicationHttpClient
     */
    private $coreApplicationHttpClient;

    /**
     * @param LoggerInterface $logger
     * @param WorkerService $workerService
     * @param TaskService $taskService
     * @param CoreApplicationHttpClient $coreApplicationHttpClient
     */
    public function __construct(
        LoggerInterface $logger,
        WorkerService $workerService,
        TaskService $taskService,
        CoreApplicationHttpClient $coreApplicationHttpClient
    ) {
        $this->logger = $logger;
        $this->workerService = $workerService;
        $this->taskService = $taskService;
        $this->coreApplicationHttpClient = $coreApplicationHttpClient;
    }

    /**
     * @param $limit
     */
    public function setWorkerProcessCount($limit)
    {
        $this->workerProcessCount = $limit;
    }

    /**
     * @param $factor
     */
    public function setMaxTasksRequestFactor($factor)
    {
        $this->maxTasksRequestFactor = $factor;
    }

    /**
     * @return int
     */
    public function getWorkerProcessCount()
    {
        return $this->workerProcessCount;
    }

    /**
     * @return int
     */
    public function getMaxTasksRequestFactor()
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
                'worker_hostname' => $this->workerService->get()->getHostname(),
                'worker_token' => $this->workerService->get()->getActivationToken(),
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
        return (int)round($this->getWorkerProcessCount() * $this->getMaxTasksRequestFactor());
    }

    /**
     * @return int
     */
    private function getLowerLimit()
    {
        return $this->getWorkerProcessCount();
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
