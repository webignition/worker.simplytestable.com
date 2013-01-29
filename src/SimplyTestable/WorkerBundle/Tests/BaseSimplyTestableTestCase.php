<?php

namespace SimplyTestable\WorkerBundle\Tests;

abstract class BaseSimplyTestableTestCase extends BaseTestCase {
    
    const TASK_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\TaskController';    
    const VERIFY_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\VerifyController';    
    
    /**
     *
     * @param string $methodName
     * @param array $postData
     * @return \SimplyTestable\WorkerBundle\Controller\TaskController
     */
    protected function getTaskController($methodName, $postData = array()) {
        return $this->getController(self::TASK_CONTROLLER_NAME, $methodName, $postData);
    }
    

    /**
     *
     * @param string $methodName
     * @param array $postData
     * @return \SimplyTestable\WorkerBundle\Controller\VerifyController
     */
    protected function getVerifyController($methodName, $postData = array()) {
        return $this->getController(self::VERIFY_CONTROLLER_NAME, $methodName, $postData);
    }    
    
    
    /**
     * 
     * @param string $controllerName
     * @param string $methodName
     * @return Symfony\Bundle\FrameworkBundle\Controller\Controller
     */
    private function getController($controllerName, $methodName, array $postData = array(), array $queryData = array()) {        
        return $this->createController($controllerName, $methodName, $postData, $queryData);
    }
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\WorkerService
     */
    protected function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }  
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\ResqueQueueService
     */
    protected function getRequeQueueService() {
        return $this->container->get('simplytestable.services.resquequeueservice');
    }    
    
    
    /**
     * 
     * @param string $url
     * @param string $type
     * @return \stdClass
     */
    protected function createTask($url, $type) {
        $response = $this->getTaskController('createAction', array(
            'url' => $url,
            'type' => $type
        ))->createAction();
        
        return json_decode($response->getContent());
    }    


}
