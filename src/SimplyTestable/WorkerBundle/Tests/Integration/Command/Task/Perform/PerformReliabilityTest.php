<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformReliabilityTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }     
    
    /**
     * @group integration
     * @group integration-standard
     * @group integration-travis
     * @group integration-standard-travis
     */    
    public function testTest() {        
        $taskObject = $this->createTask('http://unreliable.simplytestable.com/?error=503&probability=0.5', 'HTML validation');        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));      
        
        $this->assertEquals(0, $response);       
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());
    }    


}
