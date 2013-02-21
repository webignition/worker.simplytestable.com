<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class MaintenanceDisableReadOnlyCommandTest extends ConsoleCommandBaseTestCase {

    public function testEnableReadOnlyModeCorrectlyChangesState() {
        $this->setupDatabase();
        $this->getWorkerService()->setReadOnly();
      
        $response = $this->runConsole('simplytestable:maintenance:disable-read-only');        
        $this->assertEquals(0, $response);
        $this->assertEquals('worker-active', $this->getWorkerService()->get()->getState()->getName());
    }


}
