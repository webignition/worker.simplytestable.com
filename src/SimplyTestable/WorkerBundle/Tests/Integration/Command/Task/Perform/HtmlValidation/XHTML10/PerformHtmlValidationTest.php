<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\HtmlValidation\XHTML10;

use SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\HtmlValidation\BaseHtmlValidationIntegrationTest;

class PerformHtmlValidationTest extends BaseHtmlValidationIntegrationTest {
    
    /**
     * @group integration
     * @group integration-html-validation
     * @group integration-travis
     * @group integration-html-validation-travis
     */      
    public function testMinimalBasicNoErrors() {        
        $taskObject = $this->createTask('http://html-validation.simplytestable.com/xhtml10/minimal-basic-no-errors', 'HTML validation');
        
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
    public function testMinimalFramesetNoErrors() {        
        $taskObject = $this->createTask('http://html-validation.simplytestable.com/xhtml10/minimal-frameset-no-errors', 'HTML validation');
        
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
    public function testMinimalStrictNoErrors() {        
        $taskObject = $this->createTask('http://html-validation.simplytestable.com/xhtml10/minimal-strict-no-errors', 'HTML validation');
        
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
    public function testMinimalTransitionalNoErrors() {        
        $taskObject = $this->createTask('http://html-validation.simplytestable.com/xhtml10/minimal-transitional-no-errors', 'HTML validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(0, $task->getOutput()->getErrorCount());        
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());
    }    


}
