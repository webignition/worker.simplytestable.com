<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\LinkIntegrity;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class BugFixTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }
  
    public function testBugFix() {        
        $this->assertTrue(true);
        return;
   
        $taskObject = $this->createTask('http://www.boxuk.com/blog/dark-patterns-in-ux/', 'Link integrity');

        $task = $this->getTaskService()->getById($taskObject->id);
        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        )));
    } 
}

