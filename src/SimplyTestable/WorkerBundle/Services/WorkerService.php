<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Services\CoreApplicationService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Model\RemoteEndpoint;

class WorkerService extends EntityService {
    
    const WORKER_NEW_STATE = 'worker-new';
    const WORKER_ACTIVATE_REMOTE_ENDPOINT_IDENTIFIER = 'worker-activate';    
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\ThisWorker';   
    
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
     * @param string $hostname
     * @param \SimplyTestable\WorkerBundle\Services\CoreApplicationService $coreApplicationService 
     * @param \SimplyTestable\WorkerBundle\Services\StateService $stateService
     * @param \webignition\Http\Client\Client $httpClient 
     */
    public function __construct(
            EntityManager $entityManager,
            $url,
            \SimplyTestable\WorkerBundle\Services\CoreApplicationService $coreApplicationService,
            \SimplyTestable\WorkerBundle\Services\StateService $stateService,
            \webignition\Http\Client\Client $httpClient)
    {    
        parent::__construct($entityManager);
        
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
    
    
    public function activate() {        
//        $coreApplications = $this->coreApplicationService->findAll();
//        foreach ($coreApplications as $coreApplication) {
//            /* @var $coreApplication \SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication */
//            $this->coreApplicationService->populateRemoteEndpoints($coreApplication);
//            
//            $remoteEndpoint = $this->getWorkerActivateRemoteEndpoint($coreApplication);
//            
//
//           
//            $httpRequest = new \HttpRequest($remoteEndpoint->getUrl(), HTTP_METH_GET);
//            
//            //$httpRequest->send();
//            
//            $response = $this->httpClient->getResponse($httpRequest);
//            
//            var_dump($httpRequest);
//            exit();
//
//        }
//        
        var_dump("cp01");
        exit();
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
    public function isNew() {
        return $this->get()->getState()->equals($this->stateService->fetch(self::WORKER_NEW_STATE));
    }
    
}