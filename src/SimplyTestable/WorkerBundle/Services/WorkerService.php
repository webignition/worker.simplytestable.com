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
use webignition\Http\Client\CurlException;

class WorkerService extends EntityService {
    
    const WORKER_NEW_STATE = 'worker-new';
    const WORKER_ACTIVATE_STATE = 'worker-active';
    const WORKER_AWAITING_ACTIVATION_VERIFICATION_STATE = 'worker-awaiting-activation-verification';
    const WORKER_ACTIVATE_REMOTE_ENDPOINT_IDENTIFIER = 'worker-activate';    
    const WORKER_MAINTENANCE_READ_ONLY_STATE = 'worker-maintenance-read-only';
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
     * @var \Guzzle\Http\Client
     */
    private $httpClient; 
    
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Service\UrlService $urlService
     */
    private $urlService;
    

    /**
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param Logger $logger
     * @param string $salt
     * @param string $hostname
     * @param \SimplyTestable\WorkerBundle\Services\CoreApplicationService $coreApplicationService 
     * @param \SimplyTestable\WorkerBundle\Services\StateService $stateService
     * @param \SimplyTestable\WorkerBundle\Services $httpClientService
     * @param \SimplyTestable\WorkerBundle\Services\UrlService $urlService
     */
    public function __construct(
            EntityManager $entityManager,
            Logger $logger,
            $salt,
            $hostname,
            \SimplyTestable\WorkerBundle\Services\CoreApplicationService $coreApplicationService,
            \SimplyTestable\WorkerBundle\Services\StateService $stateService,
            \SimplyTestable\WorkerBundle\Services\HttpClientService $httpClientService,
            \SimplyTestable\WorkerBundle\Services\UrlService $urlService)
    {    
        parent::__construct($entityManager);
        
        $this->logger = $logger;
        $this->salt = $salt;
        $this->hostname = $hostname;
        $this->coreApplicationService = $coreApplicationService;
        $this->stateService = $stateService;
        $this->httpClient = $httpClientService->get();
        //$this->httpClient->redirectHandler()->enable();  
        $this->urlService = $urlService;
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
     * @return itn 
     */
    public function activate() {
        $this->logger->info("WorkerService::activate: Initialising");
        
        if (!$this->isNew()) {
            $this->logger->info("WorkerService::activate: This worker is not new and cannot be activated");
            return 0;
        }        

        /* @var $coreApplication \SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication */
        $coreApplication = $this->coreApplicationService->get();
        $this->coreApplicationService->populateRemoteEndpoints($coreApplication);
        
        $thisWorker = $this->get();
 
        $remoteEndpoint = $this->getWorkerActivateRemoteEndpoint($coreApplication);
        
        $httpRequest = $remoteEndpoint->getHttpRequest();
        $requestUrl = $this->urlService->prepare($remoteEndpoint->getHttpRequest()->getUrl());
        $httpRequest->setUrl($requestUrl);
        
        $httpRequest->setPostFields(array(
            'hostname' => $thisWorker->getHostname(),
            'token' => $thisWorker->getActivationToken()
        ));

        $this->logger->info("WorkerService::activate: Requesting activation with " . $requestUrl);
        
        try {
            $response = $this->httpClient->getResponse($httpRequest);
            
            if ($this->httpClient instanceof \webignition\Http\Mock\Client\Client) {
                $this->logger->info("WorkerService:activate: response fixture path: " . $this->httpClient->getStoredResponseList()->getRequestFixturePath($httpRequest));
                
                if (file_exists($this->httpClient->getStoredResponseList()->getRequestFixturePath($httpRequest))) {
                    $this->logger->info("WorkerService:activate: response fixture path: found");
                } else {
                    $this->logger->info("WorkerService:activate: response fixture path: not found");
                }                
            }            
            
            $this->logger->info("WorkerService::activate: " . $requestUrl . ": " . $response->getResponseCode()." ".$response->getResponseStatus());
            
            if ($response->getResponseCode() === 503) {
                $this->logger->err("WorkerService::activate: Activation request failed (core application is in read-only mode)");
                return $response->getResponseCode();
            }
            
            if ($response->getResponseCode() !== 200) {
                $this->logger->err("WorkerService::activate: Activation request failed");
                return $response->getResponseCode();
            }
            
            $thisWorker->setNextState();
            $this->persistAndFlush($thisWorker);

            return 0;            
            
        } catch (CurlException $curlException) {
            $this->logger->err("WorkerService::activate: " . $requestUrl . ": " . $curlException->getMessage());            
            return $curlException->getCode();
        }
        
        return 1;
    }
    
    
    public function setActive() {
        $activeState = $this->getActiveState();
        $thisWorker = $this->get()->setState($activeState);
        $this->persistAndFlush($thisWorker);
    }
    
    
    public function verify() {        
        if (!$this->isAwaitingActivationVerification()) {
            $this->logger->info("WorkerService::verify: This worker is not awaiting activation verification");
            return true;
        }          
        
        $thisWorker = $this->get();        
        $thisWorker->setNextState();
        $this->persistAndFlush($thisWorker);
        
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
        return $this->get()->getState()->equals($this->getStartingState());
    }
    
    /**
     *
     * @return boolean
     */
    private function isAwaitingActivationVerification() {
        return $this->get()->getState()->equals($this->getAwaitingActivationVerificationState());
    }

    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\State
     */    
    public function getStartingState() {
        return $this->stateService->fetch(self::WORKER_NEW_STATE);
    }
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\State
     */
    public function getAwaitingActivationVerificationState() {
        return $this->stateService->fetch(self::WORKER_AWAITING_ACTIVATION_VERIFICATION_STATE);
    }    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\State
     */       
    public function getActiveState() {
        return $this->stateService->fetch(self::WORKER_ACTIVATE_STATE);
    }
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\State
     */       
    public function getMaintenanceReadOnlyState() {
        return $this->stateService->fetch(self::WORKER_MAINTENANCE_READ_ONLY_STATE);
    }
    

    /**
     * 
     * @return boolean
     */
    public function isActive() {
        return $this->get()->getState()->equals($this->getActiveState());
    }    
    
    
    /**
     * 
     * @return boolean
     */
    public function isMaintenanceReadOnly() {
        return $this->get()->getState()->equals($this->getMaintenanceReadOnlyState());
    }
        
    
    /**
     * 
     */
    public function setReadOnly() {
        $thisWorker = $this->get();
        $thisWorker->setState($this->getMaintenanceReadOnlyState());
        $this->persistAndFlush($thisWorker);
    }
    
    
    /**
     * 
     */
    public function clearReadOnly() {
        $this->setActive();
    }
}