<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class TaskPerformCommandTest extends ConsoleCommandBaseTestCase {

    public function testPerformInvalidTestReturnsStatusCode1() {
        $this->setupDatabase();                    
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(1, $response);
    }
    
    
    public function testPerformInMaintenanceReadOnlyModeReturnsStatusCode2() {
        $this->setupDatabase();                    
        $this->createTask('http://example.com/', 'HTML validation');
        
        $this->getWorkerService()->setReadOnly();
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(2, $response);
    }
    
    
    public function testPerformInMaintenanceReadOnlyModeReturnsResqueJobToQueue() {
        $this->setupDatabase();                    
        $this->createTask('http://example.com/', 'HTML validation');
        
        $this->getWorkerService()->setReadOnly();
        
        $this->runConsole('simplytestable:task:perform', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));        
        
        $this->assertTrue($this->getRequeQueueService()->contains('task-perform', array(
            'id' => 1
        )));       
    }

}
