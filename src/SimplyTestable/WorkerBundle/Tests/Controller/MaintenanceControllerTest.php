<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;


class MaintenanceControllerTest extends BaseControllerJsonTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->removeAllTasks();
    }

    /**
     * @group standard
     */    
    public function testEnableReadOnlyAction() {        
        $response = $this->getMaintenanceController('enableReadOnlyAction')->enableReadOnlyAction();
        $this->assertEquals('["Set state to maintenance-read-only"]', $response->getContent());
        $this->assertTrue($this->getWorkerService()->isMaintenanceReadOnly());
        $this->assertFalse($this->getWorkerService()->isActive());
    }
    
    /**
     * @group standard
     */    
    public function testDisableReadOnlyAction() {        
        $response = $this->getMaintenanceController('disableReadOnlyAction')->disableReadOnlyAction();
        $this->assertEquals('["Set state to active"]', $response->getContent());
        $this->assertFalse($this->getWorkerService()->isMaintenanceReadOnly());
        $this->assertTrue($this->getWorkerService()->isActive());
    }    
    
    /**
     * @group standard
     */    
    public function testLeaveReadOnlyAction() {
        $response = $this->getMaintenanceController('leaveReadOnlyAction')->leaveReadOnlyAction();
        $this->assertEquals('["Set state to active","0 completed tasks ready to be enqueued","0 queued tasks ready to be enqueued"]', $response->getContent());
        $this->assertFalse($this->getWorkerService()->isMaintenanceReadOnly());
        $this->assertTrue($this->getWorkerService()->isActive());
    }     
    
}


