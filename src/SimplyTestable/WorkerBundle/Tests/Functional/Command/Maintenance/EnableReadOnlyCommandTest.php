<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class EnableReadOnlyCommandTest extends BaseSimplyTestableTestCase
{
    public function testRun()
    {
        $command = new EnableReadOnlyCommand(
            $this->container->get('simplytestable.services.workerservice')
        );

        $returnCode = $command->run(
            new ArrayInput([]),
            new StringOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertEquals(
            WorkerService::WORKER_MAINTENANCE_READ_ONLY_STATE,
            $this->getWorkerService()->get()->getState()->getName()
        );
    }
}
