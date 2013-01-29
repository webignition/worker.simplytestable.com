<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;


abstract class TaskControllerTest extends BaseControllerJsonTestCase {
    
    const TASK_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\TaskController';
    
    public function setUp() {
        parent::setUp();
        $this->setupDatabase();
        $this->getWorkerService()->activate();
    }
    
    
    /**
     * 
     * @param string $action
     * @return \SimplyTestable\WorkerBundle\Controller\TaskController
     */
    protected function createTaskControllerForAction($action, $postData = array()) {
        return $this->createController(self::TASK_CONTROLLER_NAME, $action, $postData);        
    }
    
    protected function setActiveState() {
        $this->getWorkerService()->activate();
    }
    
    protected function setMaintenanceReadOnlyState() {
        $this->getWorkerService()->setReadOnly();
    }
    
    

    
    
    /**
     * 
     * @param array $tasks
     * @return \stdClass
     */
    protected function createTaskCollection($tasks) {
        $controller = $this->getTaskController('createCollectionAction', array(
            'tasks' => $tasks
        ));
        
        $response = $controller->createCollectionAction();
        return json_decode($response->getContent());        
    }
    
    
    
}


