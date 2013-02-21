<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class MaintenanceEnableReadOnlyCommandTest extends ConsoleCommandBaseTestCase {

    public function testEnableReadOnlyModeCorrectlyChangesState() {
        $this->setupDatabase();
        $this->getWorkerService()->setActive();
      
        $response = $this->runConsole('simplytestable:maintenance:enable-read-only');        
        $this->assertEquals(0, $response);
        $this->assertEquals('worker-maintenance-read-only', $this->getWorkerService()->get()->getState()->getName());
    }


}
