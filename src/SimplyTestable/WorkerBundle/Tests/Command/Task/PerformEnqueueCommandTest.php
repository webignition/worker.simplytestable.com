<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformEnqueueCommandTest extends ConsoleCommandBaseTestCase {
    
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\WorkerBundle\Command\Task\PerformEnqueueCommand()
        );
    }   
    
    
    /**
     * @group standard
     */    
    public function testEnqueueTaskPerformJobs() {        
        $taskPropertyCollection = array(
            array(
                'url' => 'http://example.com/1/',
                'type' => 'HTML validation'
            ),
            array(
                'url' => 'http://example.com/1/',
                'type' => 'CSS validation'
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
        foreach ($taskPropertyCollection as $taskProperties) {
            $tasks[] = $this->createTask($taskProperties['url'], $taskProperties['type']);
        }       
  
        $this->assertEquals(0, $this->executeCommand('simplytestable:task:perform:enqueue'));
        
        foreach ($tasks as $task) {
            $this->assertTrue($this->getRequeQueueService()->contains('task-perform', array(
                'id' => $task->id
            )));             
        }
    }
}
