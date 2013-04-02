<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class WorkerActivateCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }       

    /**
     * @group standard
     */    
    public function testSuccessfulActivateWorker() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses'))); 
        
        $thisWorker = $this->getWorkerService()->get();
        $thisWorker->setState($this->getWorkerService()->getStartingState());
        $this->getWorkerService()->getEntityManager()->persist($thisWorker);
        $this->getWorkerService()->getEntityManager()->flush();
        
        $response = $this->runConsole('simplytestable:worker:activate');
        
        $this->assertEquals(self::CONSOLE_COMMAND_SUCCESS, $response);
    }
    
    /**
     * @group standard
     */
    public function test404FailureActivateWorker() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses'))); 
        
        $thisWorker = $this->getWorkerService()->get();
        $thisWorker->setState($this->getWorkerService()->getStartingState());
        $this->getWorkerService()->getEntityManager()->persist($thisWorker);
        $this->getWorkerService()->getEntityManager()->flush();        
        
        $response = $this->runConsole('simplytestable:worker:activate');
        
        $this->assertEquals(404, $response);
    }
    
    /**
     * @group standard
     */    
    public function testActivationInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {
        $this->getWorkerService()->setReadOnly();
        
        $this->assertEquals(-1, $this->runConsole('simplytestable:worker:activate'));
    }
    
    /**
     * @group standard
     */    
    public function testActivationWithCoreApplicationInMaintenanceReadOnlyModeReturnsStatusCode503() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $thisWorker = $this->getWorkerService()->get();
        $thisWorker->setState($this->getWorkerService()->getStartingState());
        $this->getWorkerService()->getEntityManager()->persist($thisWorker);
        $this->getWorkerService()->getEntityManager()->flush(); 
        
        $this->assertEquals(503, $this->runConsole('simplytestable:worker:activate'));
    }    

}
