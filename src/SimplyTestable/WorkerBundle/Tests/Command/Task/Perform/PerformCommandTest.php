<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

abstract class PerformCommandTest extends ConsoleCommandBaseTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->removeAllTasks();
        $this->removeAllTestTaskTypes(); 
    }

    public function tearDown() {
        $this->clearRedis();
        parent::tearDown();
    }
    
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\WorkerBundle\Command\Task\PerformCommand()
        );
    }
}
