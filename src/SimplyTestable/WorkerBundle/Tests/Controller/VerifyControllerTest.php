<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;


class VerifyControllerTest extends BaseControllerJsonTestCase {
    
    const WORKER_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\VerifyController';

    public function testIndexAction() {
        $this->setupDatabase();
        
        $thisWorker = $this->getWorkerService()->get();
        $thisWorker->setNextState();
    
        $_POST = array(
            'hostname' => $thisWorker->getHostname(),
            'token' => $thisWorker->getActivationToken()
        );        
        
        /* @var $controller \SimplyTestable\WorkerBundle\Controller\VerifyController */
        $controller = $this->createController(self::WORKER_CONTROLLER_NAME, 'indexAction');
        
        
        
//        $this->container->get('simplytestable.services.httpClient')->getStoredResponseList()->setFixturesPath(
//            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'                
//        );
//      
        $response = $controller->indexAction();
        
        //$this->getWorkerService()->getEntityRepository()->clear();
        //$thisWorker = $this->getWorkerService()->get();

//        
//        $worker = $this->getWorkerService()->get($_POST['hostname']);
//        $activationRequest = $this->getWorkerRequestActivationService()->get($worker);
//        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($thisWorker->getState()->equals($this->getWorkerService()->getActiveState()));
//        $this->assertEquals($hostname, $worker->getHostname());        
//        $this->assertTrue($activationRequest->getState()->equals($this->getWorkerRequestActivationService()->getStartingState()));
//        $this->assertTrue($activationRequest->getWorker()->equals($worker));                
    }
    
//    public function testActivateActionWithMissingHostname() {
//        $this->setupDatabase();
//        
//        $token = 'valid-token';
//        
//        $_POST = array(
//            'token' => $token
//        );        
//        
//        try {
//            $this->createController(self::WORKER_CONTROLLER_NAME, 'activateAction');
//        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
//            return $this->assertEquals(400, $httpException->getStatusCode());
//        } 
//        
//        $this->fail('WorkerController::activateAction() didn\'t throw a 400 HttpException for a missing hostname');  
//    }
//    
//    public function testActivateActionWithMissingToken() {
//        $this->setupDatabase();
//        
//        $hostname = 'test.worker.simplytestable.com';
//        
//        $_POST = array(
//            'hostname' => $hostname
//        );        
//        
//        try {
//            $this->createController(self::WORKER_CONTROLLER_NAME, 'activateAction');
//        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
//            return $this->assertEquals(400, $httpException->getStatusCode());
//        } 
//        
//        $this->fail('WorkerController::activateAction() didn\'t throw a 400 HttpException for a missing hostname');  
//    }    
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }   
    
    
    
//    /**
//     *
//     * @return \SimplyTestable\ApiBundle\Services\WorkerActivationRequestService 
//     */
//    private function getWorkerRequestActivationService() {
//        return $this->container->get('simplytestable.services.workeractivationrequestservice');
//    }
    
}


