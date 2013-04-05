<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class BugFixTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }
  
    public function testBugFix() {        
        $taskObject = $this->createTask('http://www.gmxmotorbikes.com.au/gmx-sports-atv-70cc-spider-2012-model-red.html', 'CSS validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
    } 
}

