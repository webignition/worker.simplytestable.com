<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class WorkerActivateCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }       

    public function testSuccessfulActivateWorker() {        
        $thisWorker = $this->getWorkerService()->get();
        $thisWorker->setState($this->getWorkerService()->getStartingState());
        $this->getWorkerService()->getEntityManager()->persist($thisWorker);
        $this->getWorkerService()->getEntityManager()->flush();
        
        $response = $this->runConsole('simplytestable:worker:activate', array(
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(self::CONSOLE_COMMAND_SUCCESS, $response);
    }
    

    public function test404FailureActivateWorker() {        
        $thisWorker = $this->getWorkerService()->get();
        $thisWorker->setState($this->getWorkerService()->getStartingState());
        $this->getWorkerService()->getEntityManager()->persist($thisWorker);
        $this->getWorkerService()->getEntityManager()->flush();        
        
        $response = $this->runConsole('simplytestable:worker:activate', array(
            '/invalid-fixtures-path-forces-mock-http-client-to-respond-with-404' => true
        ));
        
        $this->assertEquals(404, $response);
    }
    
    public function testActivationInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {
        $this->getWorkerService()->setReadOnly();
        
        $this->assertEquals(-1, $this->runConsole('simplytestable:worker:activate', array(
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        )));
    }
    
    public function testActivationWithCoreApplicationInMaintenanceReadOnlyModeReturnsStatusCode503() {
        $thisWorker = $this->getWorkerService()->get();
        $thisWorker->setState($this->getWorkerService()->getStartingState());
        $this->getWorkerService()->getEntityManager()->persist($thisWorker);
        $this->getWorkerService()->getEntityManager()->flush(); 
        
        $this->assertEquals(503, $this->runConsole('simplytestable:worker:activate', array(
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        )));
    }    

}
