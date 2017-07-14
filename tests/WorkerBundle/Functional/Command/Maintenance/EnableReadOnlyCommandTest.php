<?php

namespace Tests\WorkerBundle\Functional\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class EnableReadOnlyCommandTest extends BaseSimplyTestableTestCase
{
    public function testRun()
    {
        $command = $this->container->get(EnableReadOnlyCommand::class);

        $returnCode = $command->run(
            new ArrayInput([]),
            new StringOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertEquals(
            WorkerService::WORKER_MAINTENANCE_READ_ONLY_STATE,
            $this->container->get(WorkerService::class)->get()->getState()->getName()
        );
    }
}
