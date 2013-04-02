<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;

class ReportCompletionEnqueueCommandTest extends ConsoleCommandBaseTestCase {
    
    /**
     * @group standard
     */    
    public function testEnqueueTaskReportCompletionJobs() {
        $this->setupDatabase();        
        
        $taskPropertyCollection = array(
            array(
                'url' => 'http://example.com/1/',
                'type' => 'HTML validation'
            ),
            array(
                'url' => 'http://example.com/1/',
                'type' => 'JS static analysis'
            ),
            array(
                'url' => 'http://example.com/2/',
                'type' => 'HTML validation'
            ),            
            array(
                'url' => 'http://example.com/3/',
                'type' => 'HTML validation'
            ),             
        );
        
        $tasks = array();        
        foreach ($taskPropertyCollection as $taskIndex => $taskProperties) {
            $taskObject = $this->createTask($taskProperties['url'], $taskProperties['type']);               

            $task = $this->getTaskService()->getById($taskObject->id);
            $taskTimePeriod = new TimePeriod();
            $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
            $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));

            $task->setTimePeriod($taskTimePeriod);

            $this->createCompletedTaskOutputForTask($task);
            
            $tasks[] = $task;
        }
        
        $this->assertTrue($this->clearRedis());
       
        $response = $this->runConsole('simplytestable:task:reportcompletion:enqueue');        
        $this->assertEquals(0, $response);
        
        foreach ($tasks as $task) {
            $this->assertTrue($this->getRequeQueueService()->contains('task-report-completion', array(
                'id' => $task->getId()
            )));             
        }
    }
}
