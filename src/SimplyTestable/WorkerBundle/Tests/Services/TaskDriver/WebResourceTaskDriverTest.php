<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

class WebResourceTaskDriverTest extends BaseTest {
    
    private $taskTypeName = null;
    private $taskTypeNames = array(
        'HTML Validation',
        'CSS Validation',
        'JS Static Analysis',
        'URL Discovery',
        'Link Integrity'
    );
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }
    
    public function setUp() {
        parent::setUp();        
        $this->clearMemcacheHttpCache();
    }
    
    protected function getTaskTypeName() {
        return $this->taskTypeName;
    }
    
    /**
     * @group standard
     */     
    public function testInvalidContentType() {
        foreach ($this->taskTypeNames as $taskTypeName) {
            $this->taskTypeName = $taskTypeName;
            
            $this->setHttpFixtures($this->buildHttpFixtureSet(array(
                'HTTP/1.0 200 Ok'."\n".'Content-Type:invalid/made-it-up'
            )));

            $task = $this->getDefaultTask();

            $this->assertEquals(0, $this->getTaskService()->perform($task));

            $this->assertEquals(0, $task->getOutput()->getErrorCount());
            $this->assertEquals(0, $task->getOutput()->getWarningCount());

            $this->assertEquals($this->getTaskService()->getSkippedState(), $task->getState());            
        }
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
        foreach ($this->taskTypeNames as $taskTypeName) {
            $this->taskTypeName = $taskTypeName;
            
            $responseFixtureContent = ($mode === 'http')
                ? 'HTTP/1.0 ' . $errorCode
                : 'CURL/' . $errorCode .' Non-relevant worded error';

            $this->setHttpFixtures($this->buildHttpFixtureSet(array(
                $responseFixtureContent,
                $responseFixtureContent
            )));
            $this->getHttpClientService()->disablePlugin('Guzzle\Plugin\Backoff\BackoffPlugin');

            $task = $this->getDefaultTask();
            $this->assertEquals(0, $this->getTaskService()->perform($task));

            $decodedTaskOutput = json_decode($task->getOutput()->getOutput());

            $expectedMessageId = ($mode === 'http')
                ? 'http-retrieval-' . $errorCode
                : 'http-retrieval-curl-code-' . $errorCode;

            $this->assertEquals($expectedMessageId, $decodedTaskOutput->messages[0]->messageId);              
        }
    }

}
