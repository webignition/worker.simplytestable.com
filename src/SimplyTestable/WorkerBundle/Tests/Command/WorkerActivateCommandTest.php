<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class WorkerActivateCommandTest extends ConsoleCommandBaseTestCase {

    public function testSuccessfulActivateWorker() {        
        $this->setupDatabase();
        
        $response = $this->runConsole('simplytestable:worker:activate', array(
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(self::CONSOLE_COMMAND_SUCCESS, $response);
    }
    

    public function test404FailureActivateWorker() {        
        $this->setupDatabase();
        
        $response = $this->runConsole('simplytestable:worker:activate', array(
            '/invalid-fixtures-path-forces-mock-http-client-to-respond-with-404' => true
        ));
        
        $this->assertEquals(404, $response);
    }
    
    public function testActivationInMaintenanceReadOnlyModeReturnsStatusCode2() {
        $this->setupDatabase();
        $this->getWorkerService()->setReadOnly();
        
        $response = $this->runConsole('simplytestable:worker:activate', array(
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(-1, $response);
    }

}
