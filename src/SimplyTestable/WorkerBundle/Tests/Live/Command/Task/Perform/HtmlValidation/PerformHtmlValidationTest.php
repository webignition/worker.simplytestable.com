<?php

namespace SimplyTestable\WorkerBundle\Tests\Live\Command\Task\Perform\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformHtmlValidationTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }    

    /**
     * @group live
     * @group live-travis
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


}
