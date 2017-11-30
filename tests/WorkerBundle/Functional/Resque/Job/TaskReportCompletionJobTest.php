<?php

namespace Tests\WorkerBundle\Functional\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionCommand;
use SimplyTestable\WorkerBundle\Resque\Job\TaskReportCompletionJob;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Factory\TestTaskFactory;

class TaskReportCompletionJobTest extends AbstractJobTest
{
    const QUEUE = 'task-report-completion';

    public function testRunWithInvalidTask()
    {
        $job = $this->createJob(
            ['id' => 1],
            self::QUEUE,
            $this->container->get(ReportCompletionCommand::class)
        );
        $this->assertInstanceOf(TaskReportCompletionJob::class, $job);

        $returnCode = $job->run([]);

        $this->assertEquals(ReportCompletionCommand::RETURN_CODE_TASK_DOES_NOT_EXIST, $returnCode);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->container->get(WorkerService::class)->setReadOnly();
        $this->clearRedis();
        $task = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([]));

        $job = $this->createJob(
            ['id' => $task->getId()],
            self::QUEUE,
            $this->container->get(ReportCompletionCommand::class)
        );
        $this->assertInstanceOf(TaskReportCompletionJob::class, $job);

        $returnCode = $job->run([]);

        $this->assertEquals(ReportCompletionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }
}
