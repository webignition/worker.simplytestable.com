<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation\TaskDriverTest;

class DefaultTest extends TaskDriverTest {
    
    /**
     * @group standard
     */        
    public function testProcessingValidatorResultsGetsCorrectErrorCount() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );                
       
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        
        $this->assertEquals(3, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    }
    
    
    /**
     * @group standard
     */        
    public function testFailGracefullyWhenContentIsServedAsTextHtmlButIsNot() {                        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));       
        
        $outputObject = json_decode($task->getOutput()->getOutput());
        
        $this->assertEquals('document-is-not-markup', $outputObject->messages[0]->messageId);
    }
    
    
    /**
     * @group standard
     */        
    public function testCharacterEncodingFailureSetsTaskStateAsFailed() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );        
      
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        
        $this->assertEquals($this->getTaskService()->getFailedNoRetryAvailableState(), $task->getState());
    } 
    
    
    /**
     * @group standard
     */        
    public function testFailIncorrectWebResourceType() {           
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));       
        $this->assertEquals($this->getTaskService()->getSkippedState(), $task->getState());
    } 
    
    
    /**
     * @group standard
     */        
    public function testOnHttpAuthenticationProtectedUrl() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );
        
        $task = $this->getTask('http://unreliable.simplytestable.com/http-auth/index.html', array(
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'            
        ));
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));       
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());      
    }
    
    
    /**
     * @group standard
     */     
    public function testOnHttpAuthenticationWithInvalidCredentials() {                
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $task = $this->getTask('http://unreliable.simplytestable.com/http-auth/index.html', array(
            'http-auth-username' => 'wrong-username',
            'http-auth-password' => 'wrong-password'            
        ));
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals('{"messages":[{"message":"Unauthorized","messageId":"http-retrieval-401","type":"error"}]}', $task->getOutput()->getOutput());        
    }
    
    
    /**
     * @group standard
     */     
    public function testBugfixRedmine392() {     
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));        
        
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));        
        $this->assertEquals('{"messages":[{"message":"Internal Server Error","messageId":"http-retrieval-500","type":"error"}]}', $task->getOutput()->getOutput());      
    }

}
