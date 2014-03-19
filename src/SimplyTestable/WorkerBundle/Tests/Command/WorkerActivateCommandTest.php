<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class WorkerActivateCommandTest extends ConsoleCommandBaseTestCase {
    
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\WorkerBundle\Command\WorkerActivateCommand()
        );
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
        
        $this->assertEquals(self::CONSOLE_COMMAND_SUCCESS, $this->executeCommand('simplytestable:worker:activate'));
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
        
        $this->assertEquals(404, $this->executeCommand('simplytestable:worker:activate'));
    }
    
    /**
     * @group standard
     */    
    public function testActivationInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {
        $this->getWorkerService()->setReadOnly();

        $this->assertEquals(-1, $this->executeCommand('simplytestable:worker:activate'));
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

        $this->assertEquals(503, $this->executeCommand('simplytestable:worker:activate'));
    }    

}
