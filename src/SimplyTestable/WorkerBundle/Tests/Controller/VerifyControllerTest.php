<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;


class VerifyControllerTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }

    
    /**
     * @group standard
     */    
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
    
    
    /**
     * @group standard
     */    
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


