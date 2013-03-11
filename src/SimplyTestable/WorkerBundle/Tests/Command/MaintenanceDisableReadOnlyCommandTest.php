<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class MaintenanceDisableReadOnlyCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }       

    public function testEnableReadOnlyModeCorrectlyChangesState() {
        $this->getWorkerService()->setReadOnly();
   
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:disable-read-only'));
        $this->assertEquals('worker-active', $this->getWorkerService()->get()->getState()->getName());
    }


}
