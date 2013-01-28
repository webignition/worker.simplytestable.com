<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;

class TaskControllerCancelTest extends TaskControllerTest {
    
    const TASK_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\TaskController';
    
    public function setUp() {
        parent::setUp();
        $this->setupDatabase();
    }

    public function testCancelActionInNormalState() {
        $this->setActiveState();
        
        $url = 'http://example.com/';
        $type = 'HTML validation';        
        
        $createdTask = $this->createTask($url, $type);
        
        $postData = array(
            'id' => $createdTask->id
        );        
        
        $controller = $this->getTaskController('cancelAction', $postData);
       
        $cancellationResponse = $controller->cancelAction();        
        $cancelledTask = json_decode($cancellationResponse->getContent());
        
        $this->assertEquals(200, $cancellationResponse->getStatusCode());
        $this->assertEquals('cancelled', $cancelledTask->state);      
    }
    
    
    public function testCancelActionInMaintenanceReadOnlyStateReturnsHttp503() {                
        $this->setActiveState();
        
        $url = 'http://example.com/';
        $type = 'HTML validation';        
        
        $createdTask = $this->createTask($url, $type);
        
        $postData = array(
            'id' => $createdTask->id
        );  
        
        $this->setMaintenanceReadOnlyState();        
        
        $cancellationResponse =$this->getTaskController('cancelAction', $postData)->cancelAction();        
        $this->assertEquals(503, $cancellationResponse->getStatusCode());    
    }
    
    
    public function testCancelCollectionActionInNormalState() {
        $this->setActiveState();

        $taskData = array(
                array(
                    'url' => 'http://example.com/one/',
                    'type' => 'HTML validation'
                ),
                array(
                    'url' => 'http://example.com/two/',
                    'type' => 'CSS validation'
                ),
                array(
                    'url' => 'http://example.com/three/',
                    'type' => 'JS static analysis'
                ),              
        );        
        
        $tasks = $this->createTaskCollection($taskData);
        
        $taskIds = array();
        foreach ($tasks as $task) {
            $taskIds[] = $task->id;
        }
        
        $controller = $this->getTaskController('cancelCollectionAction', array(
            'ids' => implode(',', $taskIds)
        ));
       
        $cancellationResponse = $controller->cancelCollectionAction();      
        $this->assertEquals(200, $cancellationResponse->getStatusCode());          
    }
    
    
    public function testCancelCollectionActionInMaintenanceReadOnlyStateReturnsHttp503() {
        $this->setActiveState();

        $taskData = array(
                array(
                    'url' => 'http://example.com/one/',
                    'type' => 'HTML validation'
                ),
                array(
                    'url' => 'http://example.com/two/',
                    'type' => 'CSS validation'
                ),
                array(
                    'url' => 'http://example.com/three/',
                    'type' => 'JS static analysis'
                ),              
        );        
        
        $tasks = $this->createTaskCollection($taskData);
        
        $taskIds = array();
        foreach ($tasks as $task) {
            $taskIds[] = $task->id;
        }
        
        $this->setMaintenanceReadOnlyState();
        
        $controller = $this->getTaskController('cancelCollectionAction', array(
            'ids' => implode(',', $taskIds)
        ));
       
        $cancellationResponse = $controller->cancelCollectionAction();      
        $this->assertEquals(503, $cancellationResponse->getStatusCode());          
    }
    
  
    
}


