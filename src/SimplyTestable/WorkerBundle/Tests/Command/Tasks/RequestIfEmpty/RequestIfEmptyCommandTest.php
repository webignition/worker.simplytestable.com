<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\RequestIfEmpty;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Command\Tasks\RequestIfEmptyCommand;

abstract class RequestIfEmptyCommandTest extends ConsoleCommandBaseTestCase {

    public function setUp() {
        parent::setUp();
        $this->removeAllTasks();
        $this->clearRedis();
    }

    public function testReturnStatusCode() {
        $this->assertEquals(0, $this->executeCommand('simplytestable:tasks:requestifempty'));
    }
    
    protected function getAdditionalCommands() {
        return [
            new RequestIfEmptyCommand()
        ];
    }
}
