<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class TaskPerformEnqueueCommandTest extends ConsoleCommandBaseTestCase {
    
    public function testTest() {
        $this->setupDatabase(); 
        
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
       
        $response = $this->runConsole('simplytestable:task:perform:enqueue');        
        $this->assertEquals(0, $response);
        
        foreach ($tasks as $task) {
            $this->assertTrue($this->getRequeQueueService()->contains('task-perform', array(
                'id' => $task->id
            )));             
        }
    }
}
