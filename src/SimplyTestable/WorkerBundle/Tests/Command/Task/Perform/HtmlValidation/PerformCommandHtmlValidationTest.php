<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\PerformCommandTaskTypeTest;

class PerformCommandHtmlValidationTest extends PerformCommandTaskTypeTest {
    
    const TASK_TYPE_NAME = 'HTML validation';
    
    protected function getTaskTypeName() {
        return self::TASK_TYPE_NAME;
    }
    

    /**
     * @group standard
     */        
    public function testProcessingValidatorResultsGetsCorrectErrorCount() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );        
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());   
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(3, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    }
    
    
    /**
     * @group standard
     */        
    public function testFailGracefullyWhenContentIsServedAsTextHtmlButIsNot() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());   
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        
        $outputObject = json_decode($task->getOutput()->getOutput());
        
        $this->assertEquals('document-is-not-markup', $outputObject->messages[0]->messageId);
    }
    
    
    /**
     * @group standard
     */        
    public function testValidatorReturnsHTTP500() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());    
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
      
        $outputObject = json_decode($task->getOutput()->getOutput());
        $this->assertEquals('validator-internal-server-error', $outputObject->messages[0]->messageId);        
    }


    /**
     * @group standard
     */        
    public function testCharacterEncodingFailureSetsTaskStateAsFailed() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());    
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals($this->getTaskService()->getFailedNoRetryAvailableState(), $task->getState());
    } 
    
    
    /**
     * @group standard
     */        
    public function testFailIncorrectWebResourceType() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());    
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals($this->getTaskService()->getSkippedState(), $task->getState());
    } 
    
    
    /**
     * @group standard
     */        
    public function testOnHttpBasicAuthenticationProtectedUrl() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );        
        
        $taskObject = $this->createTask('http://unreliable.simplytestable.com/http-auth/index.html', 'HTML validation', json_encode(array(
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'
        )));  
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());      
    }
    
    
    /**
     * @group standard
     */     
    public function testOnHttpBasicAuthenticationWithInvalidCredentials() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $taskObject = $this->createTask('http://unreliable.simplytestable.com/http-auth/index.html', 'HTML validation', json_encode(array(
            'http-auth-username' => 'wrong-username',
            'http-auth-password' => 'wrong-password'
        )));
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals('{"messages":[{"message":"Unauthorized","messageId":"http-retrieval-401","type":"error"}]}', $task->getOutput()->getOutput());        
        $this->assertTrue($task->getParametersObject()->{'x-http-auth-tried'});
    }   
    
    
    /**
     * @group standard
     */        
    public function testOnHttpDigestAuthenticationProtectedUrl() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );        
        
        $taskObject = $this->createTask('http://unreliable.simplytestable.com/http-auth/index.html', 'HTML validation', json_encode(array(
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'
        )));  
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());      
        $this->assertTrue($task->getParametersObject()->{'x-http-auth-tried'});
    }
    
    
    /**
     * @group standard
     */        
    public function testOnHttpDigestAuthenticationWithInvalidCredentials() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://unreliable.simplytestable.com/http-auth/index.html', 'HTML validation', json_encode(array(
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'
        )));  
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals('{"messages":[{"message":"Unauthorized","messageId":"http-retrieval-401","type":"error"}]}', $task->getOutput()->getOutput());
        $this->assertTrue($task->getParametersObject()->{'x-http-auth-tried'});
    } 
    
    
    /**
     * @group standard
     */     
    public function testBugfixRedmine392() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://www.teksystems.com/http%3A//teksystemsaut-com.allegisgroup.com/', 'HTML validation');  
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals('{"messages":[{"message":"Internal Server Error","messageId":"http-retrieval-500","type":"error"}]}', $task->getOutput()->getOutput());      
    }
    
}
