<?php

namespace App\Tests\Unit\Resque\Job;

use App\Command\Task\PrepareCommand;
use App\Resque\Job\TaskPrepareJob;
use Mockery\Mock;
use Psr\Log\LoggerInterface;

class TaskPrepareJobTest extends AbstractJobTest
{
    /**
     * @throws \Exception
     */
    public function testRunInMaintenanceReadOnlyMode()
    {
        $taskId = 1;

        /* @var Mock|LoggerInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->with(sprintf(
                '%s: task [%d] returned %d',
                TaskPrepareJob::class,
                $taskId,
                PrepareCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE
            ));

        $logger
            ->shouldReceive('error')
            ->with(sprintf(
                '%s: task [%d] output ',
                TaskPrepareJob::class,
                $taskId
            ));

        $jobArgs = [
            'id' => $taskId,
        ];

        $job = new TaskPrepareJob();

        $this->createJob(
            $job,
            $jobArgs,
            PrepareCommand::class,
            PrepareCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $logger
        );

        $this->assertSame(PrepareCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $job->run($jobArgs));
    }
}
