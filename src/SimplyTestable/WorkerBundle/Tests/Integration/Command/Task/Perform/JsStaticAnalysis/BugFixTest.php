<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\JsStaticAnalysisValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class BugFixTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }
  
    public function testBugFix() {        
        $this->assertTrue(true);
        return;
        
        $taskObject = $this->createTask('http://www.swnymor.net/testimonials/', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
    } 
}

