<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;

class PerformCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    

    public function testPerformInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {                 
        $task = $this->createTask('http://example.com/', 'HTML validation');        
        $this->getWorkerService()->setReadOnly();
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->id => true
        ));
        
        $this->assertEquals(-1, $response);
    }
    
    
    public function testPerformInMaintenanceReadOnlyModeReturnsResqueJobToQueue() {
        $this->clearRedis();
        
        $task = $this->createTask('http://example.com/', 'HTML validation');
        
        $this->getWorkerService()->setReadOnly();
        
        $this->runConsole('simplytestable:task:perform', array(
            $task->id => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));        
        
        $this->assertTrue($this->getRequeQueueService()->contains('task-perform', array(
            'id' => $task->id
        )));       
    }    
    
    public function testPerformInvalidTestReturnsStatusCodeMinus2() {        
        $response = $this->runConsole('simplytestable:task:perform', array(
            -1 => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(-2, $response);
    }   
    
    
    public function testPerformForTestInInvalidStateReturnsStatusCodeMinus3() {        
        $taskObject = $this->createTask('http://example.com/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        $task->setState($this->getTaskService()->getCompletedState());
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(-3, $response);
    }
    
    public function testPerformTestWhereNoTaskDriverFoundReturnsStatusCodeMinus4() {        
        $taskObject = $this->createTask('http://example.com/', 'HTML validation');
        $task = $this->getTaskService()->getById($taskObject->id);
        
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
            $task->getId() => true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->assertEquals(-4, $response);
    }  


}
