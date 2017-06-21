<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Maintenance;

use SimplyTestable\WorkerBundle\Tests\Functional\Command\ConsoleCommandBaseTestCase;

class DisableReadOnlyCommandTest extends ConsoleCommandBaseTestCase
{
    public function testEnableReadOnlyModeCorrectlyChangesState()
    {
        $this->assertEquals(0, $this->executeCommand('simplytestable:maintenance:disable-read-only'));
        $this->assertEquals('worker-active', $this->getWorkerService()->get()->getState()->getName());
    }
}

