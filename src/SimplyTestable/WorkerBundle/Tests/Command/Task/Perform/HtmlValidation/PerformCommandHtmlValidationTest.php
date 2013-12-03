<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\PerformCommandTaskTypeTest;

class PerformCommandHtmlValidationTest extends PerformCommandTaskTypeTest {
    
    const TASK_TYPE_NAME = 'HTML validation';
    
    protected function getTaskTypeName() {
        return self::TASK_TYPE_NAME;
    }
    
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


}
