<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformRedirectLimitTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }     
    
    /**
     * @group integration
     * @group integration-standard
     * @group integration-travis
     * @group integration-standard-travis
     */    
    public function testRedirectLimitHandling() {        
        $taskObject = $this->createTask('http://simplytestable.com/redirect-limit-test/', 'HTML validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));      
        
        $this->assertEquals(0, $response);
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        
        $this->assertEquals('{"messages":[{"message":"Redirect limit of 4 redirects reached","messageId":"http-retrieval-redirect-limit-reached","type":"error"}]}', $task->getOutput()->getOutput());
    }    


}
