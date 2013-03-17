<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;

class PerformAllCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    

    public function testPerformAll() {                 
        $task = $this->createTask('http://example.com/', 'HTML validation');        
        $response = $this->runConsole('simplytestable:task:reportcompletion:all');
        
        $this->assertEquals(0, $response);
    }  


}
