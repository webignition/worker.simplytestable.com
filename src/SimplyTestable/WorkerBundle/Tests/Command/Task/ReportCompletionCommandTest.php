<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;

class ReportCompletionCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }     
    
    
    /**
     * @group standard
     */    
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
    

    
    /**
     * @group standard
     */    
    public function testReportCompletionForInvalidTaskReturnsStatusCodeMinus2() {        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            -1 => true
        ));
        
        $this->assertEquals(-2, $response);        
    }    
    
    /**
     * @group standard
     */    
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
    
    
    /**
     * @group standard
     */    
    public function testReportCompletionWhenInvalidCoreApplicationHostReturnsCurlCode6() {                
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        $coreApplication = $this->getCoreApplicationService()->get();
        
        $coreApplication->setUrl('http://example.com/');
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
    
    
    /**
     * @group standard
     */    
    public function testReportCompletionInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {        
        $this->getWorkerService()->setReadOnly();
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            1 => true
        ));
        
        $this->assertEquals(-1, $response);
    }
    
    
    /**
     * @group standard
     */    
    public function testReportCompletionWhenCoreApplicationInMaintenanceReadOnlyModeReturnsStatusCode503() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

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
    
    /**
     * @group standard
     */    
    public function testReportCompletionSuccessfullyDeletesTask() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        $taskObject = $createdTask = $this->createTask('http://example.com/', 'HTML validation');                
        
        $task = $this->getTaskService()->getById($taskObject->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $this->assertNotNull($task->getId());
        $this->assertNotNull($task->getOutput()->getId());
        $this->assertNotNull($task->getTimePeriod()->getId());        
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertNull($this->getTaskService()->getById($createdTask->id));
        
        $this->assertNull($task->getId());
        $this->assertNull($task->getOutput()->getId());
        $this->assertNull($task->getTimePeriod()->getId());
    } 
    
    
    /**
     * @group standard
     */      
    public function testReportCompletionRemovesTemporaryTaskParameters() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://unreliable.simplytestable.com/http-auth/index.html', 'HTML validation', json_encode(array(
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'
        )));              
        
        $task = $this->getTaskService()->getById($taskObject->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));
        
        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);
        
        $this->assertNotNull($task->getId());
        $this->assertNotNull($task->getOutput()->getId());
        $this->assertNotNull($task->getTimePeriod()->getId());        
        
        $taskParameters = $task->getParametersObject();
        $taskParameters->{'x-http-auth-tried'} = true;

        $task->setParameters(json_encode($taskParameters));           
        $this->getTaskService()->persistAndFlush($task);
        
        $this->assertTrue($task->hasParameter('http-auth-username'));
        $this->assertTrue($task->hasParameter('http-auth-password'));
        $this->assertTrue($task->hasParameter('x-http-auth-tried'));
        
        $response = $this->runConsole('simplytestable:task:reportcompletion', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);
        
        $this->assertTrue($task->hasParameter('http-auth-username'));
        $this->assertTrue($task->hasParameter('http-auth-password'));
        $this->assertFalse($task->hasParameter('x-http-auth-tried'));        
    }

}
