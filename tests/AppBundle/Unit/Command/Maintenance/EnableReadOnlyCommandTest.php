<?php

namespace Tests\AppBundle\Unit\Command\Maintenance;

use Mockery\Mock;
use SimplyTestable\AppBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\AppBundle\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/Maintenance/EnableReadOnlyCommand
 */
class EnableReadOnlyCommandTest extends \PHPUnit\Framework\TestCase
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
