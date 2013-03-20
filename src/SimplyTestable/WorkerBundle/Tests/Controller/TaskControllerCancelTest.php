<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;

class TaskControllerCancelTest extends TaskControllerTest {    
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }

    public function testCancelActionInNormalState() {
        $url = 'http://example.com/';
        $type = 'HTML validation';        
        
        $createdTask = $this->createTask($url, $type);
        $this->assertInstanceOf('\SimplyTestable\WorkerBundle\Entity\Task\Task', $this->getTaskService()->getById($createdTask->id));
        
        $postData = array(
            'id' => $createdTask->id
        );        
        
        $controller = $this->getTaskController('cancelAction', $postData);
       
        $cancellationResponse = $controller->cancelAction();        
        $this->assertEquals(200, $cancellationResponse->getStatusCode());
        
        $this->assertNull($this->getTaskService()->getById($createdTask->id));     
    }
    
    
    public function testCancelActionInMaintenanceReadOnlyStateReturnsHttp503() {
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
        
        foreach ($taskIds as $taskId) {
            $this->assertNull($this->getTaskService()->getById($taskId));
        }
        
        $this->assertEquals(200, $cancellationResponse->getStatusCode());          
    }
    
    
    public function testCancelCollectionActionInMaintenanceReadOnlyStateReturnsHttp503() {
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


