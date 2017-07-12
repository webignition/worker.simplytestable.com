<?php

namespace Tests\WorkerBundle\Functional\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class DisableReadOnlyCommandTest extends BaseSimplyTestableTestCase
{
    public function testRun()
    {
        $command = new DisableReadOnlyCommand(
            $this->container->get('simplytestable.services.workerservice')
        );

        $returnCode = $command->run(
            new ArrayInput([]),
            new StringOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertEquals(
            WorkerService::WORKER_ACTIVE_STATE,
            $this->getWorkerService()->get()->getState()->getName()
        );
    }
}
