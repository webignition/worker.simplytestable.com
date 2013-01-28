<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;


class TaskControllerCreateTest extends TaskControllerTest {
    
    const TASK_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\TaskController';
    
    public function setUp() {
        parent::setUp();
        $this->setupDatabase();        
    }

    public function testCreateActionInNormalState() {
        $this->setActiveState();
        
        $url = 'http://example.com/';
        $type = 'HTML validation';
        
        $postData = array(
            'url' => $url,
            'type' => $type
        ); 
        
        $controller = $this->getTaskController('createAction', $postData);        
        $response = $controller->createAction();
    
        $responseObject = json_decode($response->getContent());
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $responseObject->id);
        $this->assertEquals($url, $responseObject->url);
        $this->assertEquals('queued', $responseObject->state);
        $this->assertEquals($type, $responseObject->type);          
    }
    
    
    public function testCreateCollectionActionInNormalState() {
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
        
        $postData = array(
            'tasks' => $taskData
        );
        
        $controller = $this->getTaskController('createCollectionAction', $postData);
        
        $response = $controller->createCollectionAction();
        $responseObject = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, count($responseObject));
        
        foreach ($responseObject as $responseTaskIndex => $responseTask) {
            $this->assertEquals($responseTaskIndex + 1, $responseTask->id);
            $this->assertEquals($taskData[$responseTaskIndex]['url'], $responseTask->url);
            $this->assertEquals('queued', $responseTask->state);
            $this->assertEquals($taskData[$responseTaskIndex]['type'], $responseTask->type);               
        }       
    }
    
    public function testCreateActionInMaintenanceReadOnlyStateReturnsHttp503() {
        $this->setMaintenanceReadOnlyState();      
        
        $response = $this->getTaskController('createAction', array(
            'url' => 'http://example.com/',
            'type' => 'HTML validation'
        ))->createAction();        
     
        $this->assertEquals(503, $response->getStatusCode());      
    }      
    
    
    public function testCreateCollectionActionInMaintenanceReadOnlyStateReturnsHttp503() {
        $this->setMaintenanceReadOnlyState();  
        
        $response = $this->getTaskController('createCollectionAction', array(
            'tasks' => array(
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
            )
        ))->createCollectionAction();        
    
        $this->assertEquals(503, $response->getStatusCode());      
    }    
    
}


