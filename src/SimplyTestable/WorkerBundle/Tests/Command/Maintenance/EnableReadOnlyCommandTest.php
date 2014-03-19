<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Maintenance;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class EnableReadOnlyCommandTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    } 
    
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand(),
        );
    }

    /**
     * @group standard
     */   
    public function testEnableReadOnlyModeCorrectlyChangesState() {     
        $this->assertEquals(0, $this->executeCommand('simplytestable:maintenance:enable-read-only'));
        $this->assertEquals('worker-maintenance-read-only', $this->getWorkerService()->get()->getState()->getName());
    }


}
