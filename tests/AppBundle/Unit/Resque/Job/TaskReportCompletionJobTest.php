<?php

namespace Tests\AppBundle\Unit\Resque\Job;

use Mockery\Mock;
use Psr\Log\LoggerInterface;
use SimplyTestable\AppBundle\Command\Task\ReportCompletionCommand;
use SimplyTestable\AppBundle\Resque\Job\TaskReportCompletionJob;

class TaskReportCompletionJobTest extends AbstractJobTest
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
                TaskReportCompletionJob::class,
                $taskId,
                ReportCompletionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE
            ));

        $logger
            ->shouldReceive('error')
            ->with(sprintf(
                '%s: task [%d] output ',
                TaskReportCompletionJob::class,
                $taskId
            ));

        $jobArgs = [
            'id' => $taskId,
        ];

        $job = new TaskReportCompletionJob();

        $this->createJob(
            $job,
            $jobArgs,
            ReportCompletionCommand::class,
            ReportCompletionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $logger
        );

        $this->assertSame(ReportCompletionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $job->run($jobArgs));
    }
}
