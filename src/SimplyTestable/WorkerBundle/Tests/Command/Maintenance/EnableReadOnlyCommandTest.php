<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class EnableReadOnlyCommandTest extends ConsoleCommandBaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAdditionalCommands()
    {
        return [
            new EnableReadOnlyCommand(),
        ];
    }

    public function testEnableReadOnlyModeCorrectlyChangesState()
    {
        $this->assertEquals(0, $this->executeCommand('simplytestable:maintenance:enable-read-only'));
        $this->assertEquals('worker-maintenance-read-only', $this->getWorkerService()->get()->getState()->getName());
    }
}

