<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\HtmlValidation\HTML5;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformHtmlValidationTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }    

    /**
     * @group integration
     * @group integration-travis
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
     * @group integration-travis
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
     * @group integration-travis
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


}
