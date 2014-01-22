<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class TaskDriverTest extends BaseSimplyTestableTestCase {

    public function setUp() {
        parent::setUp();
        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        
    }
    
    /**
     * @group standard
     */        
    public function testProcessingValidatorResultsGetsCorrectErrorCount() {        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );                
       
        $task = $this->getTaskService()->getById($this->createTask('http://example.com/', 'HTML Validation')->id);
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        
        $this->assertEquals(3, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    }
    
    
    /**
     * @group standard
     */        
    public function testFailGracefullyWhenContentIsServedAsTextHtmlButIsNot() {                
        $task = $this->getTaskService()->getById($this->createTask('http://example.com/', 'HTML Validation')->id);
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));       
        
        $outputObject = json_decode($task->getOutput()->getOutput());
        
        $this->assertEquals('document-is-not-markup', $outputObject->messages[0]->messageId);
    }
    
    
    /**
     * @group standard
     */        
    public function testCharacterEncodingFailureSetsTaskStateAsFailed() {        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );        
      
        $task = $this->getTaskService()->getById($this->createTask('http://example.com/', 'HTML Validation')->id);
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        
        $this->assertEquals($this->getTaskService()->getFailedNoRetryAvailableState(), $task->getState());
    } 
    
    
    /**
     * @group standard
     */        
    public function testFailIncorrectWebResourceType() {           
        $task = $this->getTaskService()->getById($this->createTask('http://example.com/', 'HTML Validation')->id);
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));       
        $this->assertEquals($this->getTaskService()->getSkippedState(), $task->getState());
    } 
    
    
    /**
     * @group standard
     */        
    public function testOnHttpAuthenticationProtectedUrl() {        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );         
       
        $task = $this->getTaskService()->getById($this->createTask('http://unreliable.simplytestable.com/http-auth/index.html', 'HTML validation', json_encode(array(
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'
        )))->id);
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));       
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());      
    }
    
    
    /**
     * @group standard
     */     
    public function testOnHttpAuthenticationWithInvalidCredentials() {                
        $task = $this->getTaskService()->getById($this->createTask('http://unreliable.simplytestable.com/http-auth/index.html', 'HTML validation', json_encode(array(
            'http-auth-username' => 'wrong-username',
            'http-auth-password' => 'wrong-password'
        )))->id);
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals('{"messages":[{"message":"Unauthorized","messageId":"http-retrieval-401","type":"error"}]}', $task->getOutput()->getOutput());        
    }
    
    
    /**
     * @group standard
     */     
    public function testBugfixRedmine392() {     
        $task = $this->getTaskService()->getById($this->createTask('http://www.teksystems.com/http%3A//teksystemsaut-com.allegisgroup.com/', 'HTML validation')->id);
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));        
        $this->assertEquals('{"messages":[{"message":"Internal Server Error","messageId":"http-retrieval-500","type":"error"}]}', $task->getOutput()->getOutput());      
    }    

}
