<?php

namespace SimplyTestable\WorkerBundle\Controller;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class MaintenanceController extends BaseController
{   
    
    public function enableReadOnlyAction()
    {
        $this->getWorkerService()->setReadOnly();        
        return $this->sendResponse();
    }
    
    
    /**
     *
     * @return WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }
    
    
    
}
