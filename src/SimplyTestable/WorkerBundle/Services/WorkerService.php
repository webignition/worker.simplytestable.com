<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Services\CoreApplicationService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Model\RemoteEndpoint;
use webignition\Http\Client\Client as HttpClient;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;

class WorkerService extends EntityService {
    
    const WORKER_NEW_STATE = 'worker-new';
    const WORKER_ACTIVATE_REMOTE_ENDPOINT_IDENTIFIER = 'worker-activate';    
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\ThisWorker';
    
    
    /**
     *
     * @var Logger
     */
    private $logger;
    
    
    /**
     *
     * @var string
     */
    private $salt;

    
    /**
     *
     * @var string
     */
    private $hostname;    
    
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\CoreApplicationService 
     */
    private $coreApplicationService;
    
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\StateService 
     */
    private $stateService;
    
    
    /**
     *
     * @var \webignition\Http\Client\Client
     */
    private $httpClient; 
    

    /**
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param Logger $logger
     * @param string $salt
     * @param string $hostname
     * @param \SimplyTestable\WorkerBundle\Services\CoreApplicationService $coreApplicationService 
     * @param \SimplyTestable\WorkerBundle\Services\StateService $stateService
     * @param \webignition\Http\Client\Client $httpClient 
     */
    public function __construct(
            EntityManager $entityManager,
            Logger $logger,
            $salt,
            $hostname,
            \SimplyTestable\WorkerBundle\Services\CoreApplicationService $coreApplicationService,
            \SimplyTestable\WorkerBundle\Services\StateService $stateService,
            \webignition\Http\Client\Client $httpClient)
    {    
        parent::__construct($entityManager);
        
        $this->logger = $logger;
        $this->salt = $salt;
        $this->hostname = $hostname;
        $this->coreApplicationService = $coreApplicationService;
        $this->stateService = $stateService;
        $this->httpClient = $httpClient;
        $this->httpClient->redirectHandler()->enable();        
    }  
    
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }     
    
    
    /**
     *
     * @return ThisWorker
     */
    public function get() {
        if (!$this->has()) {
            $this->create();
        }
        
        return $this->fetch();
    }
    
    /**
     *
     * @return boolean
     */
    private function has() {
        return !is_null($this->fetch());
    }
    
    /**
     *
     * @return ThisWorker 
     */
    private function fetch() {
        return $this->getEntityRepository()->find(1);
    }
    
    
    /**
     *
     * @return ThisWorker
     */
    private function create() {        
        $thisWorker = new ThisWorker();
        $thisWorker->setHostname($this->hostname);
        $thisWorker->setState($this->stateService->fetch('worker-new'));        
        $thisWorker->setActivationToken(md5($this->salt . $this->hostname));
        
        return $this->persistAndFlush($thisWorker);        
    }
    
    
    /**
     *
     * @param ThisWorker $job
     * @return ThisWorker
     */
    public function persistAndFlush(ThisWorker $thisWorker) {
        $this->getEntityManager()->persist($thisWorker);
        $this->getEntityManager()->flush();
        return $thisWorker;
    }     
    
    
    /**
     * Issue activation request to core application
     * Activation is completed when core application verifies
     * 
     * @return boolean 
     */
    public function activate() {
        $this->logger->info("WorkerService::activate: Initialising");
        
        if (!$this->isNew()) {
            $this->logger->info("WorkerService::activate: This worker is not new and cannot be activated");
            return true;
        }        

        /* @var $coreApplication \SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication */
        $coreApplication = $this->coreApplicationService->get();
        $this->coreApplicationService->populateRemoteEndpoints($coreApplication);
        
        $thisWorker = $this->get();
 
        $remoteEndpoint = $this->getWorkerActivateRemoteEndpoint($coreApplication);

        $httpRequest = $remoteEndpoint->getHttpRequest();
        $httpRequest->setPostFields(array(
            'hostname' => $thisWorker->getHostname(),
            'token' => $thisWorker->getActivationToken()
        ));

        $this->logger->info("WorkerService::activate: Requesting activation with " . $remoteEndpoint->getHttpRequest()->getUrl());

        $response = $this->httpClient->getResponse($httpRequest);

        $this->logger->info("WorkerService::activate: " . $remoteEndpoint->getHttpRequest()->getUrl() . ": " . $response->getResponseCode()." ".$response->getResponseStatus());

        if ($response->getResponseCode() !== 200) {
            $this->logger->warn("WorkerService::activate: Activation request failed");
            return false;
        }

        return true;
    }
    
    
    /**
     *
     * @param CoreApplication $coreApplication
     * @return RemoteEndpoint
     */
    private function getWorkerActivateRemoteEndpoint(CoreApplication $coreApplication) {
        $remoteEndpoint = new RemoteEndpoint();
        $remoteEndpoint->setIdentifier(self::WORKER_ACTIVATE_REMOTE_ENDPOINT_IDENTIFIER);
        
        return $coreApplication->getRemoteEndpoint($remoteEndpoint);
    } 
    
    
    /**
     *
     * @return boolean
     */
    private function isNew() {
        return $this->get()->getState()->equals($this->stateService->fetch(self::WORKER_NEW_STATE));
    }
    
}