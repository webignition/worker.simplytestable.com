<?php

namespace SimplyTestable\WorkerBundle\Tests\Live\Command\Task\Perform\HtmlValidation\XHTML10;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformHtmlValidationTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }
    
    
    /**
     * @group live
     * @group live-travis
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
     * @group live
     * @group live-travis
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
     * @group live
     * @group live-travis
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
     * @group live
     * @group live-travis
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
