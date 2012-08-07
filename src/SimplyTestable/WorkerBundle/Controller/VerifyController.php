<?php

namespace SimplyTestable\WorkerBundle\Controller;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class VerifyController extends WorkerController
{
    public function __construct() {
        $this->setInputDefinitions(array(
            'indexAction' => new InputDefinition(array(
                new InputArgument('hostname', InputArgument::REQUIRED, 'Hostname passed to {app}/worker/activate'),
                new InputArgument('token', InputArgument::REQUIRED, 'Token passed to {app}/worker/activate')
            ))
        ));
        
        $this->setRequestTypes(array(
            'indexAction' => HTTP_METH_POST
        ));
    }    
    
    public function indexAction()
    {
        $thisWorker = $this->getWorkerService()->get();
        if ($thisWorker->getHostname() != $this->getArguments('activateAction')->get('hostname')) {
            return $this->sendFailureResponse();
        }
        
        if ($thisWorker->getActivationToken() != $this->getArguments('activateAction')->get('token')) {
            return $this->sendFailureResponse();
        }
        
        $this->getWorkerService()->verify();
        return $this->sendSuccessResponse(); 
    }
    
    
    /**
     *
     * @return WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }
}
