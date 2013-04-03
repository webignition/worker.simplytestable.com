<?php

namespace SimplyTestable\WorkerBundle\Tests\Live\Command\Task\Perform\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformCssValidationTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }    

    /**
     * @group live
     */    
    public function testErrorFreeCssValidation() {        
        $taskObject = $this->createTask('http://css-validation.simplytestable.com', 'CSS validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(0, $task->getOutput()->getErrorCount());        
        $this->assertEquals('[]', $task->getOutput()->getOutput());
    }    
    

    /**
     * @group live
     */    
    public function testCssValidationRedirectLoop() {        
        $taskObject = $this->createTask('http://simplytestable.com/redirect-loop-test/', 'CSS validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(1, $task->getOutput()->getErrorCount());        
        
        $this->assertEquals('{"messages":[{"message":"Redirect loop detected","messageId":"http-retrieval-redirect-loop","type":"error"}]}', $task->getOutput()->getOutput());
    }  
    
    
    /**
     * @group live
     */    
    public function testCssValidationRedirectLimit() {        
        $taskObject = $this->createTask('http://simplytestable.com/redirect-limit-test/', 'CSS validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(1, $task->getOutput()->getErrorCount());        
        
        $this->assertEquals('{"messages":[{"message":"Redirect limit of 4 redirects reached","messageId":"http-retrieval-redirect-limit-reached","type":"error"}]}', $task->getOutput()->getOutput());
    }     


}
