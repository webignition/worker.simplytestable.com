<?php
namespace SimplyTestable\WorkerBundle\Services;

use Guzzle\Http\Exception\CurlException;
use \Psr\Log\LoggerInterface as Logger;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use SimplyTestable\WorkerBundle\Services\UrlService;
use SimplyTestable\WorkerBundle\Services\CoreApplicationService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\TaskService;

class TasksService {

    const PROCESS_LIMIT_THRESHOLD_FACTOR = 2;

    /**
     * @var Logger
     */
    private $logger;
    
    /**
     *
     * @var UrlService $urlService
     */
    private $urlService;
    
    
    /**
     *
     * @var CoreApplicationService $coreApplicationService
     */
    private $coreApplicationService;   
    
    
    /**
     *
     * @var WorkerService $workerService
     */
    private $workerService;
    
    
    /**
     *
     * @var HttpClientService
     */
    private $httpClientService;


    /**
     * @var TaskService
     */
    private $taskService;


    /**
     * @var int
     */
    private $workerProcessCount = null;

    /**
     * @param Logger $logger
     * @param UrlService $urlService
     * @param CoreApplicationService $coreApplicationService
     * @param WorkerService $workerService
     * @param HttpClientService $httpClientService
     * @param TaskService $taskService
     */
    public function __construct(
        Logger $logger,
        UrlService $urlService,
        CoreApplicationService $coreApplicationService,
        WorkerService $workerService,
        HttpClientService $httpClientService,
        TaskService $taskService)
    {
        $this->logger = $logger;
        $this->urlService = $urlService;
        $this->coreApplicationService = $coreApplicationService;
        $this->workerService = $workerService;
        $this->httpClientService = $httpClientService;
        $this->taskService = $taskService;
    }


    /**
     * @param $limit
     * @return $this
     */
    public function setWorkerProcessCount($limit) {
        $this->workerProcessCount = $limit;
        return $this;
    }


    /**
     * @return int
     */
    public function getWorkerProcessCount() {
        return $this->workerProcessCount;
    }


    public function request() {
        if (!$this->isWithinThreshold()) {
            return false;
        }

        $requestUrl = $this->urlService->prepare($this->coreApplicationService->get()->getUrl() . '/tasks/request/');
        $request = $this->httpClientService->getRequest($requestUrl . '?' . http_build_query([
                'worker_hostname' => $this->workerService->get()->getHostname(),
                'worker_token' => $this->workerService->get()->getActivationToken(),
                'limit' => $this->getLimit()
        ]));

        try {
            $response = $request->send();

            if ($response->getStatusCode() !== 200) {
                if ($response->isClientError()) {
                    throw ClientErrorResponseException::factory($request, $response);
                } elseif ($response->isServerError()) {
                    throw ServerErrorResponseException::factory($request, $response);
                }
            }

            return true;
        } catch (ClientErrorResponseException $clientErrorResponseException) {
            $this->logger->error('TaskService:request:ClientErrorResponseException [' . $clientErrorResponseException->getResponse()->getStatusCode() . ']');
        } catch (ServerErrorResponseException $serverErrorResponseException) {
            $this->logger->error('TaskService:request:ServerErrorResponseException [' . $serverErrorResponseException->getResponse()->getStatusCode() . ']');
        } catch (CurlException $curlException) {
            $this->logger->error('TaskService:request:CurlException [' . $curlException->getErrorNo() . ']');
        }

        return false;
    }


    /**
     * @return bool
     */
    private function isWithinThreshold() {
        return $this->taskService->getInCompleteCount() <= $this->getLowerLimit();
    }


    /**
     * @return int
     */
    private function getUpperLimit() {
        return (int)round($this->getWorkerProcessCount() * self::PROCESS_LIMIT_THRESHOLD_FACTOR);
    }


    /**
     * @return int
     */
    private function getLowerLimit() {
        return $this->getWorkerProcessCount();
    }


    /**
     * @return int
     */
    private function getLimit() {
        return $this->getUpperLimit() - $this->taskService->getInCompleteCount();
    }
}