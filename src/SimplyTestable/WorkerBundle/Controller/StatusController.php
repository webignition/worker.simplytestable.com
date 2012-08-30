<?php

namespace SimplyTestable\WorkerBundle\Controller;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class StatusController extends BaseController
{   
    
    public function indexAction()
    {
        $thisWorker = $this->getWorkerService()->get();
        return $this->sendResponse($thisWorker); 
    }
    
    
    /**
     *
     * @return WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }
}
