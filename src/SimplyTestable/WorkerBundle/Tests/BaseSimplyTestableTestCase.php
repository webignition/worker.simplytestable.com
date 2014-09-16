<?php

namespace SimplyTestable\WorkerBundle\Tests;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\WorkerBundle\Model\TaskDriver\Response as TaskDriverResponse;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;
use Doctrine\Common\Cache\MemcacheCache;

abstract class BaseSimplyTestableTestCase extends BaseTestCase {
    
    const TASK_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\TaskController';    
    const VERIFY_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\VerifyController';    
    const MAINTENANCE_CONTROLLER_NAME = 'SimplyTestable\WorkerBundle\Controller\MaintenanceController';        
    
    public function setUp() {
        parent::setUp();
    }    
    
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
     * @return \SimplyTestable\WorkerBundle\Services\Resque\QueueService
     */
    protected function getRequeQueueService() {
        return $this->container->get('simplytestable.services.resque.queueservice');
    }    
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TestHttpClientService
     */
    protected function getHttpClientService() {
        return $this->container->get('simplytestable.services.httpclientservice');
    }     
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TestWebResourceService
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
     * @return \SimplyTestable\WorkerBundle\Services\MemcacheService
     */
    protected function getMemcacheService() {
        return $this->container->get('simplytestable.services.memcacheservice');
    }      
    
    
    /**
     * 
     * @param string $url
     * @param string $type
     * @return \stdClass
     */
    protected function createTask($url, $type, $parameters = null) {
        $response = $this->getTaskController('createAction', array(
            'url' => $url,
            'type' => $type,
            'parameters' => $parameters
        ))->createAction();
        
        return json_decode($response->getContent());
    }
    
    
    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager() {
        return $this->container->get('doctrine')->getManager();
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
    
    
    protected function clearMemcacheHttpCache() {
        $memcacheCache = new MemcacheCache();
        $memcacheCache->setMemcache($this->getMemcacheService()->get());
        $memcacheCache->deleteAll();        
    }
    
    
    protected function removeAllTasks() {
        $this->removeAllForEntity('SimplyTestable\WorkerBundle\Entity\Task\Task');      
    }
    
    protected function removeAllTestTaskTypes() {
        $taskTypes = $this->getEntityManager()->getRepository('SimplyTestable\WorkerBundle\Entity\Task\Type\Type')->findAll();
        
        foreach ($taskTypes as $taskType) {
            if (preg_match('/test-/', $taskType->getName())) {
                $this->getEntityManager()->remove($taskType);
                $this->getEntityManager()->flush();                   
            }
        }
    }
    
    private function removeAllForEntity($entityName) {
        $entities = $this->getEntityManager()->getRepository($entityName)->findAll();
        if (is_array($entities) && count($entities) > 0) {
            foreach ($entities as $entity) {
                $this->getEntityManager()->remove($entity);                               
            }
            
            $this->getEntityManager()->flush(); 
        }        
    }
    
    
    
    protected function setHttpFixtures($fixtures) {
        foreach ($fixtures as $fixture) {
            if ($fixture instanceof \Exception) {
                $this->getHttpClientService()->getMockPlugin()->addException($fixture);
            } else {
                $this->getHttpClientService()->getMockPlugin()->addResponse($fixture);
            }
        }
    }
    
    
    protected function getHttpFixtures($path) {
        $httpMessages = array();

        $fixturesDirectory = new \DirectoryIterator($path);
        $fixturePaths = array();
        foreach ($fixturesDirectory as $directoryItem) {
            if ($directoryItem->isFile()) {                
                $fixturePaths[] = $directoryItem->getPathname();
            }
        }        
        
        sort($fixturePaths);
        
        foreach ($fixturePaths as $fixturePath) {
            $httpMessages[] = file_get_contents($fixturePath);
        }
        
        return $this->buildHttpFixtureSet($httpMessages);
    }
    
    
    /**
     * 
     * @param array $httpMessages
     * @return array
     */
    protected function buildHttpFixtureSet($items) {
        $fixtures = array();
        
        foreach ($items as $item) {
            switch ($this->getHttpFixtureItemType($item)) {
                case 'httpMessage':
                    $fixtures[] = \Guzzle\Http\Message\Response::fromMessage($item);
                    break;
                
                case 'curlException':
                    $fixtures[] = $this->getCurlExceptionFromCurlMessage($item);                    
                    break;
                
                default:
                    throw new \LogicException();
            }
        }
        
        return $fixtures;
    }
    
    private function getHttpFixtureItemType($item) {
        if (substr($item, 0, strlen('HTTP')) == 'HTTP') {
            return 'httpMessage';
        }
        
        return 'curlException';
    }
    
    
    /**
     * 
     * @param string $curlMessage
     * @return \Guzzle\Http\Exception\CurlException
     */
    private function getCurlExceptionFromCurlMessage($curlMessage) {
        $curlMessageParts = explode(' ', $curlMessage, 2);
        
        $curlException = new \Guzzle\Http\Exception\CurlException();
        $curlException->setError($curlMessageParts[1], (int)  str_replace('CURL/', '', $curlMessageParts[0]));
        
        return $curlException;
    } 
    
    
    protected function assertSystemCurlOptionsAreSetOnAllRequests() {
        foreach ($this->getHttpClientService()->getHistory()->getAll() as $httpTransaction) {
            foreach ($this->container->getParameter('curl_options') as $curlOption) {                                
                $expectedValueAsString = $curlOption['value'];
                
                if (is_string($expectedValueAsString)) {
                    $expectedValueAsString = '"'.$expectedValueAsString.'"';
                }                
                
                if (is_bool($curlOption['value'])) {
                    $expectedValueAsString = ($curlOption['value']) ? 'true' : 'false';
                }
                
                $this->assertEquals(
                    $curlOption['value'],
                    $httpTransaction['request']->getCurlOptions()->get(constant($curlOption['name'])),
                    'Curl option "'.$curlOption['name'].'" not set to ' . $expectedValueAsString . ' for ' .$httpTransaction['request']->getMethod() . ' ' . $httpTransaction['request']->getUrl()
                );
            }
        }
    }  


}
