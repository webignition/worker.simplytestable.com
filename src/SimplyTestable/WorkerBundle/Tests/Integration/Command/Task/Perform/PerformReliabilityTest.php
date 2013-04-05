<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformReliabilityTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }     
    
    /**
     * @group integration
     * @group integration-standard
     * @group integration-travis
     * @group integration-standard-travis
     */    
    public function testRetryingWhenEncounteringHttpErrors() {        
        $taskObject = $this->createTask('http://unreliable.simplytestable.com/?error=503&probability=0.5', 'HTML validation');        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));      
        
        $this->assertEquals(0, $response);       
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());
    }    

    
    /**
     * @group integration
     * @group integration-standard
     * @group integration-travis
     * @group integration-standard-travis
     */    
    public function testRetryingWhenEncounteringHttpTimeouts() {
        $httpClientCurlOptions = $this->getHttpClientService()->get()->getConfig('curl.options');
        $httpClientCurlOptions[CURLOPT_TIMEOUT] = 1;
        
        $this->getHttpClientService()->get()->getConfig()->set('curl.options', $httpClientCurlOptions);
        
        $taskObject = $this->createTask('http://unreliable.simplytestable.com/timeout/?timeout-delay=2&probability=0.5', 'HTML validation');        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));      
        
        $this->assertEquals(0, $response);       
        $this->assertEquals(true, $task->getOutput()->getOutput());
    } 
}
