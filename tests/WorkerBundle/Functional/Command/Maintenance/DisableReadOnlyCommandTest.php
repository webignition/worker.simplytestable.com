<?php

namespace Tests\WorkerBundle\Functional\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class DisableReadOnlyCommandTest extends BaseSimplyTestableTestCase
{
    public function testRun()
    {
        $command = $this->container->get(DisableReadOnlyCommand::class);

        $returnCode = $command->run(
            new ArrayInput([]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertEquals(
            WorkerService::WORKER_ACTIVE_STATE,
            $this->container->get(WorkerService::class)->get()->getState()->getName()
        );
    }
}
