<?php

namespace Tests\WorkerBundle\Unit\Resque\Job;

use Mockery\Mock;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Command\Task\PerformCommand;
use SimplyTestable\WorkerBundle\Resque\Job\TaskPerformJob;

class TaskPerformJobTest extends AbstractJobTest
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
                TaskPerformJob::class,
                $taskId,
                PerformCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE
            ));

        $logger
            ->shouldReceive('error')
            ->with(sprintf(
                '%s: task [%d] output ',
                TaskPerformJob::class,
                $taskId
            ));

        $jobArgs = [
            'id' => $taskId,
        ];

        $job = new TaskPerformJob();

        $this->createJob(
            $job,
            $jobArgs,
            PerformCommand::class,
            PerformCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $logger
        );

        $this->assertSame(PerformCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $job->run($jobArgs));
    }
}
