<?php

namespace SimplyTestable\WorkerBundle\Tests;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\WorkerBundle\Model\TaskDriver\Response as TaskDriverResponse;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;

abstract class BaseSimplyTestableTestCase extends BaseTestCase {
    
    const TASK_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\TaskController';    
    const VERIFY_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\VerifyController';    
    const MAINTENANCE_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\MaintenanceController';        
    
    protected function setActiveState() {
        $this->getWorkerService()->activate();
    }    
    
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
     * @param string $methodName
     * @param array $postData
     * @return \SimplyTestable\WorkerBundle\Controller\MaintenanceController
     */
    protected function getMaintenanceController($methodName, $postData = array()) {
        return $this->getController(self::MAINTENANCE_CONTROLLER_NAME, $methodName, $postData);
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
     * @return \SimplyTestable\WorkerBundle\Services\StateService
     */
    protected function getStateService() {
        return $this->container->get('simplytestable.services.stateservice');
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
     * @return \SimplyTestable\WorkerBundle\Services\HttpClientService
     */
    protected function getHttpClientService() {
        return $this->container->get('simplytestable.services.httpclientservice');
    }     
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\WebResourceService
     */
    protected function getWebResourceService() {
        return $this->container->get('simplytestable.services.webresourceservice');
    }     
        
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TaskService
     */
    protected function getTaskService() {
        return $this->container->get('simplytestable.services.taskservice');
    }       
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\CoreApplicationService
     */
    protected function getCoreApplicationService() {
        return $this->container->get('simplytestable.services.coreapplicationservice');
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
    
    
    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager() {
        return $this->container->get('doctrine.orm.entitymanager');
    }
    
    
    protected function createCompletedTaskOutputForTask(
            Task $task,
            $output = '1',
            $errorCount = 0,
            $warningCount = 0,
            $contentTypeString = 'application/json',
            $result = 'success', // skipped, failed, succeeded,
            $isRetryable = null,
            $isRetryLimitReached = null
    ) {
        $mediaTypeParser = new InternetMediaTypeParser();
        $contentType = $mediaTypeParser->parse($contentTypeString);
        
        $taskOutput = new TaskOutput();
        $taskOutput->setContentType($contentType);
        $taskOutput->setErrorCount($errorCount);
        $taskOutput->setOutput($output);
        $taskOutput->setState($this->getStateService()->fetch('taskoutput-queued'));
        $taskOutput->setWarningCount($warningCount);        
        
        $taskDriverResponse = new TaskDriverResponse();
        
        $taskDriverResponse->setTaskOutput($taskOutput);
        $taskDriverResponse->setErrorCount($taskOutput->getErrorCount());
        $taskDriverResponse->setWarningCount($taskOutput->getWarningCount());
        
        switch ($result) {
            case 'skipped':
                $taskDriverResponse->setHasBeenSkipped();
                break;
            
            case 'failed':
                $taskDriverResponse->setHasFailed();
                break;
            
            default:
                $taskDriverResponse->setHasSucceeded();
                break;
        }
        
        if ($isRetryable) {
            $taskDriverResponse->setIsRetryable(true);
        }
        
        if ($isRetryLimitReached) {
            $taskDriverResponse->setIsRetryLimitReached(true);
        }
        
        $this->getTaskService()->complete($task, $taskDriverResponse);
    }


}
