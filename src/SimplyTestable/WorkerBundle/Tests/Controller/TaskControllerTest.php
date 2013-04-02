<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;


abstract class TaskControllerTest extends BaseControllerJsonTestCase {

    
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


