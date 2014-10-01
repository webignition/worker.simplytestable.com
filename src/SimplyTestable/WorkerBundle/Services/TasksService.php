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


class TasksService {

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
     * @var int
     */
    private $taskRequestLimit = null;

    
    /**
     *
     * @param Logger $logger
     * @param UrlService $urlService
     * @param CoreApplicationService $coreApplicationService
     * @param WorkerService $workerService
     * @param HttpClientService $httpClientService
     */
    public function __construct(
        Logger $logger,
        UrlService $urlService,
        CoreApplicationService $coreApplicationService,
        WorkerService $workerService,
        HttpClientService $httpClientService)
    {
        $this->logger = $logger;
        $this->urlService = $urlService;
        $this->coreApplicationService = $coreApplicationService;
        $this->workerService = $workerService;
        $this->httpClientService = $httpClientService;
    }


    /**
     * @param $limit
     * @return $this
     */
    public function setTaskRequestLimit($limit) {
        $this->taskRequestLimit = $limit;
        return $this;
    }


    /**
     * @return int
     */
    public function getTaskRequestLimit() {
        return $this->taskRequestLimit;
    }


    public function request() {
        $requestUrl = $this->urlService->prepare($this->coreApplicationService->get()->getUrl() . '/tasks/request/');

        $request = $this->httpClientService->getRequest($requestUrl . '?' . http_build_query([
                'worker_hostname' => $this->workerService->get()->getHostname(),
                'worker_token' => $this->workerService->get()->getActivationToken(),
                'limit' => $this->getTaskRequestLimit()
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
}