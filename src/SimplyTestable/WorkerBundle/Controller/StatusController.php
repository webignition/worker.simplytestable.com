<?php

namespace SimplyTestable\WorkerBundle\Controller;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class StatusController extends BaseController
{   
    
    public function indexAction()
    {
        $status = array();        
        $thisWorker = $this->getWorkerService()->get();
        
        $status['hostname'] = $thisWorker->getHostname();
        $status['state'] = $thisWorker->getPublicSerializedState();
        $status['version'] = $this->getLatestGitHash();
        
       // var_dump($)
        
        return $this->sendResponse($status); 
    }
    
    
    private function getLatestGitHash() {
        return trim(shell_exec("git log | head -1 | awk {'print $2;'}"));
    }
    
    
    /**
     *
     * @return WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }
}
