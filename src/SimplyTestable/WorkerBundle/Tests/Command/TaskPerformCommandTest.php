<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;

class TaskPerformCommandTest extends ConsoleCommandBaseTestCase {

    public function testPerformInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {
        $this->setupDatabase();                    
        $this->createTask('http://example.com/', 'HTML validation');
        
        $this->getWorkerService()->setReadOnly();
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(-1, $response);
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
    
    public function testPerformInvalidTestReturnsStatusCodeMinus2() {
        $this->setupDatabase();                    
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(-2, $response);
    }   
    
    
    public function testPerformForTestInInvalidStateReturnsStatusCodeMinus3() {
        $this->setupDatabase();                    
        
        $this->createTask('http://example.com/', 'HTML validation');
        $task = $this->getTaskService()->getById(1);
        $task->setNextState();
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(-3, $response);
    }
    
    public function testPerformTestWhereNoTaskDriverFoundReturnsStatusCodeMinus4() {
        $this->setupDatabase();                    
        
        $this->createTask('http://example.com/', 'HTML validation');
        $task = $this->getTaskService()->getById(1);
        
        $unknownTaskType = new TaskType();
        $unknownTaskType->setName('Unknown task type');
        $unknownTaskType->setDescription('Description of unknown task type');
        $unknownTaskType->setClass($task->getType()->getClass());
        $this->getEntityManager()->persist($unknownTaskType);
        $this->getEntityManager()->flush();
        
        $task->setType($unknownTaskType);
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(-4, $response);
    }  


}
