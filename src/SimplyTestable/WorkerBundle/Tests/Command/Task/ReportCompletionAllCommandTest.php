<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;

class ReportCompletionAllCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    

    
    /**
     * @group standard
     */    
    public function testReportCompletionAll() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        
        $taskProperties = $this->createTask('http://example.com/', 'HTML validation');        
        
        $task = $this->getTaskService()->getById($taskProperties->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));        
        
        $task->setTimePeriod($taskTimePeriod);
        
        $this->createCompletedTaskOutputForTask($task);
        
        $response = $this->runConsole('simplytestable:task:reportcompletion:all');
        
        $this->assertEquals(0, $response);
    }  


}
