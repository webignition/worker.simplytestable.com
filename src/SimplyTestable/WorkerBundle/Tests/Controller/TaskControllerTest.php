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
    
    
    public function testCreateActionInMaintenanceReadOnlyStateReturnsHttp503() {
        $thisWorker = $this->getWorkerService()->get();
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
    
        $responseObject = json_decode($response->getContent());
        
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


