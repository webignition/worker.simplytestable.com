<?php

namespace Tests\WorkerBundle\Unit\Resque\Job;

use Mockery\Mock;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;
use SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob;

class TasksRequestJobTest extends AbstractJobTest
{
    /**
     * @throws \Exception
     */
    public function testRunInMaintenanceReadOnlyMode()
    {
        /* @var Mock|LoggerInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);

        $logger
            ->shouldReceive('error')
            ->with(sprintf(
                '%s: task [default] returned %d',
                TasksRequestJob::class,
                RequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE
            ));

        $logger
            ->shouldReceive('error')
            ->with(sprintf(
                '%s: task [default] output ',
                TasksRequestJob::class
            ));

        $jobArgs = [];

        $job = new TasksRequestJob();

        $this->createJob(
            $job,
            $jobArgs,
            RequestCommand::class,
            RequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $logger
        );

        $this->assertSame(RequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $job->run($jobArgs));
    }
}
