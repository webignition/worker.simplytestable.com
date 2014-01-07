<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

abstract class PerformCommandTaskTypeTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    } 
    
    abstract protected function getTaskTypeName();    
    
    
    /**
     * @group standard
     */     
    public function testInvalidContentType() {
        $this->clearMemcacheHttpCache();
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 200 Ok'."\n".'Content-Type:invalid/made-it-up'
        )));
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());
    
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);  
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());
        
        $this->assertEquals($this->getTaskService()->getSkippedState(), $task->getState());
    }    
    
    
    /**
     * @group standard
     */        
    public function testHttp401() {
        $this->assertCorrectFailureForGivenHttpStatusCode(str_replace('testHttp', '', $this->getName()));
    }    
    
    /**
     * @group standard
     */        
    public function testHttp404() {
        $this->assertCorrectFailureForGivenHttpStatusCode(str_replace('testHttp', '', $this->getName()));
    }

    /**
     * @group standard
     */        
    public function testHttp500() {        
        $this->assertCorrectFailureForGivenHttpStatusCode(str_replace('testHttp', '', $this->getName()));
    } 
    
    /**
     * @group standard
     */        
    public function testHttp503() {        
        $this->assertCorrectFailureForGivenHttpStatusCode(str_replace('testHttp', '', $this->getName()));
    } 
    
    
    /**
     * @group standard
     */        
    public function testCurl6() {        
        $this->assertCorrectFailureForGivenCurlCode(str_replace('testCurl', '', $this->getName()));
    }    
    
    /**
     * @group standard
     */        
    public function testCurl28() {
        $this->assertCorrectFailureForGivenCurlCode(str_replace('testCurl', '', $this->getName()));
    }    
    
    private function assertCorrectFailureForGivenHttpStatusCode($statusCode) {        
        $this->assertCorrectFailureForGivenModeAndCode('http', $statusCode);       
    } 
    
    
    private function assertCorrectFailureForGivenCurlCode($curlCode) {        
        $this->assertCorrectFailureForGivenModeAndCode('curl', $curlCode);       
    }    
    
    private function assertCorrectFailureForGivenModeAndCode($mode, $errorCode) {
        $this->clearMemcacheHttpCache();          
        
        $responseFixtureContent = ($mode === 'http')
            ? 'HTTP/1.0 ' . $errorCode
            : 'CURL/' . $errorCode .' Non-relevant worded error';
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            $responseFixtureContent
        )));
        $this->getHttpClientService()->disablePlugin('Guzzle\Plugin\Backoff\BackoffPlugin');
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());
    
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);  
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput());
        
        $expectedMessageId = ($mode === 'http')
            ? 'http-retrieval-' . $errorCode
            : 'http-retrieval-curl-code-' . $errorCode;
        
        $this->assertEquals($expectedMessageId, $decodedTaskOutput->messages[0]->messageId);          
    }
}
