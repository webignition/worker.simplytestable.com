<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;

class TaskReportCompletionCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }     
    
    public function testReportCompletionSuccessfullyReturnsStatusCode0() {
        $this->setupDatabase();        
        $taskObject = $createdTask = $this->createTask('http://example.com/', 'HTML validation');                
        
        $task = $this->getTaskService()->getById($taskObject->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            $task->getId() => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
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
        $createdTask = $this->createTask('http://example.com/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($createdTask->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-03'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            $task->getId() => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(404, $response);      
    }
    
    
    public function testReportCompletionInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {
        //$this->setupDatabase();
        //$this->getWorkerService()->activate();
        
        $createdTask = $this->createTask('http://example.com/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($createdTask->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $this->getWorkerService()->setReadOnly();
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            $task->getId() => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(-1, $response);
    }
    
    public function testReportCompletionWhenCoreApplicationInMaintenanceReadOnlyModeReturnsStatusCode503() {        
        $this->setupDatabase();
        $createdTask = $this->createTask('http://example.com/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($createdTask->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            $task->getId() => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(503, $response);
    }    

}
