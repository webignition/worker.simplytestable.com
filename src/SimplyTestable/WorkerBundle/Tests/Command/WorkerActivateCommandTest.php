<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class WorkerActivateCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }       

    public function testSuccessfulActivateWorker() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses'))); 
        
        $thisWorker = $this->getWorkerService()->get();
        $thisWorker->setState($this->getWorkerService()->getStartingState());
        $this->getWorkerService()->getEntityManager()->persist($thisWorker);
        $this->getWorkerService()->getEntityManager()->flush();
        
        $response = $this->runConsole('simplytestable:worker:activate');
        
        $this->assertEquals(self::CONSOLE_COMMAND_SUCCESS, $response);
    }
    

    public function test404FailureActivateWorker() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses'))); 
        
        $thisWorker = $this->getWorkerService()->get();
        $thisWorker->setState($this->getWorkerService()->getStartingState());
        $this->getWorkerService()->getEntityManager()->persist($thisWorker);
        $this->getWorkerService()->getEntityManager()->flush();        
        
        $response = $this->runConsole('simplytestable:worker:activate');
        
        $this->assertEquals(404, $response);
    }
    
    public function testActivationInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {
        $this->getWorkerService()->setReadOnly();
        
        $this->assertEquals(-1, $this->runConsole('simplytestable:worker:activate'));
    }
    
    public function testActivationWithCoreApplicationInMaintenanceReadOnlyModeReturnsStatusCode503() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $thisWorker = $this->getWorkerService()->get();
        $thisWorker->setState($this->getWorkerService()->getStartingState());
        $this->getWorkerService()->getEntityManager()->persist($thisWorker);
        $this->getWorkerService()->getEntityManager()->flush(); 
        
        $this->assertEquals(503, $this->runConsole('simplytestable:worker:activate'));
    }    

}
