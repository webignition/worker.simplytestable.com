<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;

class ReportCompletionAllCommandTest extends ConsoleCommandBaseTestCase {
    
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\WorkerBundle\Command\Task\ReportCompletionAllCommand()
        );
    }   

    
    /**
     * @group standard
     */    
    public function testReportCompletionAll() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));        
        $this->removeAllTasks();
        
        $taskProperties = $this->createTask('http://example.com/', 'HTML validation');        
        
        $task = $this->getTaskService()->getById($taskProperties->id);
        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));        
        
        $task->setTimePeriod($taskTimePeriod);
        
        $this->createCompletedTaskOutputForTask($task);
        
        $this->assertEquals(0, $this->executeCommand('simplytestable:task:reportcompletion:all'));
    }  


}
