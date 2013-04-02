<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;

class ReportCompletionCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }     
    
    public function testReportCompletionSuccessfullyReturnsStatusCode0() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        $taskObject = $createdTask = $this->createTask('http://example.com/', 'HTML validation');                
        
        $task = $this->getTaskService()->getById($taskObject->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertNull($this->getTaskService()->getById($createdTask->id));
    }
    

    public function testReportCompletionForInvalidTaskReturnsStatusCodeMinus2() {        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            -1 => true
        ));
        
        $this->assertEquals(-2, $response);        
    }
    
    
    public function testReportCompletionWhenNoCoreApplicationReturnsStatusCode404() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $createdTask = $this->createTask('http://example.com/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($createdTask->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-03'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            $task->getId() => true            
        ));
        
        $this->assertEquals(404, $response);      
    }
    
    
    public function testReportCompletionWhenInvalidCoreApplicationHostReturnsCurlCode6() {        
        $coreApplication = $this->getCoreApplicationService()->get();
        
        $coreApplication->setUrl('http://invalid');
        $this->getCoreApplicationService()->getEntityManager()->persist($coreApplication);
        $this->getCoreApplicationService()->getEntityManager()->flush();        
        
        $createdTask = $this->createTask('http://example.com/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($createdTask->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-03'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            $task->getId() => true            
        ));
        
        $this->assertEquals(6, $response);      
    }    
    
    
    public function testReportCompletionInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {        
        $this->getWorkerService()->setReadOnly();
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            1 => true
        ));
        
        $this->assertEquals(-1, $response);
    }
    
    public function testReportCompletionWhenCoreApplicationInMaintenanceReadOnlyModeReturnsStatusCode503() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->setupDatabase();
        $createdTask = $this->createTask('http://example.com/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($createdTask->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(503, $response);
    }    

}
