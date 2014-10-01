<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;

abstract class RequestCommandTest extends ConsoleCommandBaseTestCase {
    
    public function tearDown() {
        $this->clearRedis();
        parent::tearDown();
    }
    
    protected function getAdditionalCommands() {
        return [
            new RequestCommand()
        ];
    }
}
