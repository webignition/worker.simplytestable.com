<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Maintenance;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class DisableReadOnlyCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }       

    /**
     * @group standard
     */
    public function testEnableReadOnlyModeCorrectlyChangesState() {   
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:disable-read-only'));
        $this->assertEquals('worker-active', $this->getWorkerService()->get()->getState()->getName());
    }


}
