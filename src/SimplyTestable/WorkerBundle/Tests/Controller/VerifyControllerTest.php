<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;


class VerifyControllerTest extends BaseControllerJsonTestCase {
    
    const WORKER_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\VerifyController';
    
    public function setUp() {
        parent::setUp();
        $this->setupDatabase();
        $this->getWorkerService()->get()->setNextState();
    }

    public function testIndexAction() {
        $thisWorker = $this->getWorkerService()->get();
    
        $_POST = array(
            'hostname' => $thisWorker->getHostname(),
            'token' => $thisWorker->getActivationToken()
        );        
        
        /* @var $controller \SimplyTestable\WorkerBundle\Controller\VerifyController */
        $controller = $this->createController(self::WORKER_CONTROLLER_NAME, 'indexAction');
     
        $response = $controller->indexAction();
       
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($thisWorker->getState()->equals($this->getWorkerService()->getActiveState()));              
    }   
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    } 
    
}


