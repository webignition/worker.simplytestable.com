<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;

class TaskReportCompletionCommandTest extends ConsoleCommandBaseTestCase {
    
    public function testReportCompletionSuccessfullyReturnsStatusCode0() {
        $this->setupDatabase();
        $this->getWorkerService()->activate();
        
        $createdTask = $this->createTask('http://example.com/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($createdTask->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertNull($this->getTaskService()->getById($createdTask->id));
    }
    

    public function testReportCompletionForInvalidTaskReturnsStatusCode1() {
        $this->setupDatabase();
        $this->getWorkerService()->activate();
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(1, $response);        
    }
    
    
    public function testReportCompletionWhenNoCoreApplicationReturnsStatusCode404() {
        $this->setupDatabase();
        $this->getWorkerService()->activate();
        
        $createdTask = $this->createTask('http://example.com/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($createdTask->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-03'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(404, $response);
        $this->assertInstanceOf('SimplyTestable\WorkerBundle\Entity\Task\Task', $this->getTaskService()->getById($createdTask->id));       
    }
    
    
    public function testReportCompletionInMaintenanceReadOnlyModeReturnsStatusCode2() {
        $this->setupDatabase();
        $this->getWorkerService()->activate();
        
        $createdTask = $this->createTask('http://example.com/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($createdTask->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $this->getWorkerService()->setReadOnly();
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(2, $response);
        $this->assertInstanceOf('SimplyTestable\WorkerBundle\Entity\Task\Task', $this->getTaskService()->getById($createdTask->id));
    }
    
    
    public function testReportCompletionInMaintenanceReadOnlyModeReturnsResqueJobToQueue() {
        $this->setupDatabase();
        $this->getWorkerService()->activate();
        
        $createdTask = $this->createTask('http://example.com/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($createdTask->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $this->getWorkerService()->setReadOnly();
        
        $this->runConsole('simplytestable:task:reportcompletion', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertTrue($this->getRequeQueueService()->contains('task-report-completion', array(
            'id' => 1
        )));       
    }

}
