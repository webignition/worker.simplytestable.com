<?php
namespace SimplyTestable\WorkerBundle\Services;

//use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Services\CoreApplicationService;
use SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication;
use SimplyTestable\WorkerBundle\Model\RemoteEndpoint;

class WorkerService {
    
    const WORKER_ACTIVATE_REMOTE_ENDPOINT_IDENTIFIER = 'worker-activate';
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\CoreApplicationService 
     */
    private $coreApplicationService;
    
    
    /**
     *
     * @var \webignition\Http\Client\Client
     */
    private $httpClient;    
    

    /**
     *
     * @param \SimplyTestable\WorkerBundle\Services\CoreApplicationService $coreApplicationService 
     * @param \webignition\Http\Client\Client $httpClient 
     */
    public function __construct(
            \SimplyTestable\WorkerBundle\Services\CoreApplicationService $coreApplicationService,
            \webignition\Http\Client\Client $httpClient)
    {    
        $this->coreApplicationService = $coreApplicationService;
        $this->httpClient = $httpClient;
        $this->httpClient->redirectHandler()->enable();        
    }    
    
    
    public function activate() {        
        $coreApplications = $this->coreApplicationService->findAll();
        foreach ($coreApplications as $coreApplication) {
            /* @var $coreApplication \SimplyTestable\WorkerBundle\Entity\CoreApplication\CoreApplication */
            $this->coreApplicationService->populateRemoteEndpoints($coreApplication);
            
            $remoteEndpoint = $this->getWorkerActivateRemoteEndpoint($coreApplication);
            

           
            $httpRequest = new \HttpRequest($remoteEndpoint->getUrl(), HTTP_METH_GET);
            
            //$httpRequest->send();
            
            $response = $this->httpClient->getResponse($httpRequest);
            
            var_dump($httpRequest);
            exit();

        }
        
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
    
}