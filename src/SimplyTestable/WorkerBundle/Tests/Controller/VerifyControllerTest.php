<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;


class VerifyControllerTest extends BaseControllerJsonTestCase {
    
    const WORKER_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\VerifyController';
    
    public function setUp() {
        parent::setUp();
        $this->setupDatabase();        
        $this->getWorkerService()->get()->setNextState();
    }

    public function testVerifyingActivationOfRegularService() {
        $thisWorker = $this->getWorkerService()->get();
    
        $postData = array(
            'hostname' => $thisWorker->getHostname(),
            'token' => $thisWorker->getActivationToken()
        );
        
        $response = $this->getVerifyController('indexAction', $postData)->indexAction();
        
        $this->assertEquals(200, $response->getStatusCode());                
        $this->assertTrue($this->getWorkerService()->isActive());              
    }
    
    
    public function testVerifyingActivationInMaintenanceReadOnlyStateReturnsHttp503 () {
        $this->getWorkerService()->setReadOnly();
        $thisWorker = $this->getWorkerService()->get();
    
        $postData = array(
            'hostname' => $thisWorker->getHostname(),
            'token' => $thisWorker->getActivationToken()
        );
        
        $response = $this->getVerifyController('indexAction', $postData)->indexAction();   
       
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertTrue($this->getWorkerService()->isMaintenanceReadOnly());
    }
    
}


