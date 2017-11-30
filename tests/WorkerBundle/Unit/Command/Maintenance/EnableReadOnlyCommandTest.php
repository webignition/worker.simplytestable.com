<?php

namespace Tests\WorkerBundle\Unit\Command\Maintenance;

use Mockery\Mock;
use SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/Maintenance/EnableReadOnlyCommand
 */
class EnableReadOnlyCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @throws \Exception
     */
    public function testRun()
    {
        /* @var WorkerService|Mock $workerService */
        $workerService = \Mockery::mock(WorkerService::class);
        $workerService
            ->shouldReceive('setReadOnly')
            ->once();

        $command = new EnableReadOnlyCommand($workerService);

        $returnCode = $command->run(
            new ArrayInput([]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
