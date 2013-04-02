<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

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
            $tasks[] = $this->createTask($taskProperties['url'], $taskProperties['type']);
            $this->runConsole('simplytestable:task:perform', array(
                ($taskIndex + 1) => true                
            ));
        }
        
        $this->assertTrue($this->clearRedis());
       
        $response = $this->runConsole('simplytestable:task:reportcompletion:enqueue');        
        $this->assertEquals(0, $response);
        
        $this->assertTrue($this->getRequeQueueService()->contains('task-report-completion', array(
            'id' => $tasks[0]->id
        ))); 

        $this->assertTrue($this->getRequeQueueService()->contains('task-report-completion', array(
            'id' => $tasks[1]->id
        ))); 

        $this->assertTrue($this->getRequeQueueService()->contains('task-report-completion', array(
            'id' => $tasks[2]->id
        ))); 

        $this->assertTrue($this->getRequeQueueService()->contains('task-report-completion', array(
            'id' => $tasks[3]->id
        )));
    }
}
