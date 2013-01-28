<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;


class TaskControllerTest extends BaseControllerJsonTestCase {
    
    const TASK_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\TaskController';
    
    public function setUp() {
        parent::setUp();
        $this->setupDatabase();
        $thisWorker = $this->getWorkerService()->get();
        $thisWorker->setState($this->getWorkerService()->getActiveState());
    }

    public function testCreateActionInNormalState() {
        $thisWorker = $this->getWorkerService()->get();
        $this->assertTrue($thisWorker->getState()->equals($this->getWorkerService()->getActiveState()));
        
        $url = 'http://example.com/';
        $type = 'HTML validation';
        
        $_POST = array(
            'url' => $url,
            'type' => $type
        );       
        
        /* @var $controller \SimplyTestable\WorkerBundle\Controller\TaskController */
        $controller = $this->createController(self::TASK_CONTROLLER_NAME, 'createAction');
        
        $response = $controller->createAction();
    
        $responseObject = json_decode($response->getContent());
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $responseObject->id);
        $this->assertEquals($url, $responseObject->url);
        $this->assertEquals('queued', $responseObject->state);
        $this->assertEquals($type, $responseObject->type);          
    }
    
    
    public function testCreateCollectionActionInNormalState() {
        $thisWorker = $this->getWorkerService()->get();
        $this->assertTrue($thisWorker->getState()->equals($this->getWorkerService()->getActiveState()));
        
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
        
        $_POST = array(
            'tasks' => $taskData
        );       
        
        /* @var $controller \SimplyTestable\WorkerBundle\Controller\TaskController */
        $controller = $this->createController(self::TASK_CONTROLLER_NAME, 'createCollectionAction');
        
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
        $this->getWorkerService()->setReadOnly();
        
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
        
        $_POST = array(
            'tasks' => $taskData
        );      
        
        /* @var $controller \SimplyTestable\WorkerBundle\Controller\TaskController */
        $controller = $this->createController(self::TASK_CONTROLLER_NAME, 'createCollectionAction');
        
        $response = $controller->createCollectionAction();        
        $this->assertEquals(503, $response->getStatusCode());      
    }
    
    public function testCreateCollectionActionInMaintenanceReadOnlyStateReturnsHttp503() {
        $this->getWorkerService()->setReadOnly();
        
        $url = 'http://example.com/';
        $type = 'HTML validation';
        
        $_POST = array(
            'url' => $url,
            'type' => $type
        );       
        
        /* @var $controller \SimplyTestable\WorkerBundle\Controller\TaskController */
        $controller = $this->createController(self::TASK_CONTROLLER_NAME, 'createAction');
        
        $response = $controller->createAction();        
        $this->assertEquals(503, $response->getStatusCode());      
    }    
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TaskService
     */
    private function getTaskService() {
        return $this->container->get('simplytestable.services.taskservice');
    } 
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }     
    
}


