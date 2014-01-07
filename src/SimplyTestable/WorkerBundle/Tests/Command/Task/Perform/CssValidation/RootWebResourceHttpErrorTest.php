<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\CssValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class RootWebResourceHttpErrorTest extends ConsoleCommandBaseTestCase {    

    /**
     * @group standard
     */        
    public function test401() {
        $this->assertCorrectFailureForGivenHttpStatusCode(str_replace('test', '', $this->getName()));
    }    
    
    /**
     * @group standard
     */        
    public function test404() {
        $this->assertCorrectFailureForGivenHttpStatusCode(str_replace('test', '', $this->getName()));
    }

    /**
     * @group standard
     */        
    public function test500() {        
        $this->assertCorrectFailureForGivenHttpStatusCode(str_replace('test', '', $this->getName()));
    } 
    
    /**
     * @group standard
     */        
    public function test503() {        
        $this->assertCorrectFailureForGivenHttpStatusCode(str_replace('test', '', $this->getName()));
    }    
    
    
    public function assertCorrectFailureForGivenHttpStatusCode($statusCode) {        
        $this->clearMemcacheHttpCache();          
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 ' . $statusCode
        )));
        $this->getHttpClientService()->disablePlugin('Guzzle\Plugin\Backoff\BackoffPlugin');
        
        $taskObject = $this->createTask('http://example.com/', 'CSS Validation');         
    
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);  
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput());
        $this->assertEquals('http-retrieval-' . $statusCode, $decodedTaskOutput->messages[0]->messageId);        
    }

}
