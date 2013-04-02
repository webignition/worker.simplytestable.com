<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Maintenance;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class EnableReadOnlyCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }     

    /**
     * @group standard
     */   
    public function testEnableReadOnlyModeCorrectlyChangesState() {     
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));
        $this->assertEquals('worker-maintenance-read-only', $this->getWorkerService()->get()->getState()->getName());
    }


}
