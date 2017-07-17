<?php

namespace Tests\WorkerBundle\Functional\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class EnableReadOnlyCommandTest extends BaseSimplyTestableTestCase
{
    public function testRun()
    {
        $command = $this->container->get(EnableReadOnlyCommand::class);

        $returnCode = $command->run(
            new ArrayInput([]),
            new BufferedOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertEquals(
            WorkerService::WORKER_MAINTENANCE_READ_ONLY_STATE,
            $this->container->get(WorkerService::class)->get()->getState()->getName()
        );
    }
}
