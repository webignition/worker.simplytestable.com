<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\HtmlValidation\HTML5;

use SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\HtmlValidation\BaseHtmlValidationIntegrationTest;

class PerformHtmlValidationTest extends BaseHtmlValidationIntegrationTest {

    /**
     * @group integration
     * @group integration-html-validation
     * @group integration-travis
     * @group integration-html-validation-travis
     */ 
    public function testErrorFreeHtmlValidation() {                
        $taskObject = $this->createTask('http://html-validation.simplytestable.com', 'HTML validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(0, $task->getOutput()->getErrorCount());        
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());
    }  
   
    
    /**
     * @group integration
     * @group integration-html-validation
     * @group integration-travis
     * @group integration-html-validation-travis
     */
    public function testMinimalNoErrors() {        
        $taskObject = $this->createTask('http://html-validation.simplytestable.com/html5/minimal-no-errors.html', 'HTML validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(0, $task->getOutput()->getErrorCount());        
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());
    } 
 
    
    /**
     * @group integration
     * @group integration-html-validation
     * @group integration-travis
     * @group integration-html-validation-travis
     */
    public function testMinimalNoErrorsWithUtf8Bom() {        
        $taskObject = $this->createTask('http://html-validation.simplytestable.com/html5/minimal-with-utf8-bom.html', 'HTML validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(0, $task->getOutput()->getErrorCount());        
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());
    }      


    /**
     * @group integration
     * @group integration-html-validation
     * @group integration-travis
     * @group integration-html-validation-travis
     */       
    public function testWithSingleSpaceInUrl() {        
        $url = 'http://html-validation.simplytestable.com/url-cases/minimal-no-errors with-single-space.html';
        
        $taskObject = $this->createTask($url, 'HTML validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());        
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());
    }  
    
    
    /**
     * @group integration
     * @group integration-html-validation
     * @group integration-travis
     * @group integration-html-validation-travis
     */       
    public function testWithInvalidCharacterEncoding() {        
        $url = 'http://html-validation.simplytestable.com/html5/invalid-character-encoding.html';
        
        $taskObject = $this->createTask($url, 'HTML validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());        
        
        $outputContentObject = json_decode($task->getOutput()->getOutput());
        
        $this->assertEquals('character-encoding', $outputContentObject->messages[0]->messageId);
    }     
  
}
