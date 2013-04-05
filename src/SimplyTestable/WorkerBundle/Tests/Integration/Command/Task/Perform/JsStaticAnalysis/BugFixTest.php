<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\JsStaticAnalysisValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class BugFixTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }
  
    public function testBugFix() {        
        $taskObject = $this->createTask('http://timecapsule-candle.com/index.html', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
    } 
}

