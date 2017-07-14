<?php

namespace Tests\WorkerBundle\Functional\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionCommand;
use SimplyTestable\WorkerBundle\Resque\Job\Job;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class TaskReportCompletionJobTest extends BaseSimplyTestableTestCase
{
    const QUEUE = 'task-report-completion';

    public function testRunWithInvalidTask()
    {
        $taskReportCompletionJob = $this->createTaskReportCompletionJob(-1);

        $returnCode = $taskReportCompletionJob->run([]);

        $this->assertEquals(
            ReportCompletionCommand::RETURN_CODE_TASK_DOES_NOT_EXIST,
            $returnCode
        );
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->container->get(WorkerService::class)->setReadOnly();
        $this->clearRedis();
        $task = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([]));

        $taskReportCompletionJob = $this->createTaskReportCompletionJob($task->getId());

        $returnCode = $taskReportCompletionJob->run([]);

        $this->assertEquals(
            ReportCompletionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    /**
     * @param int $taskId
     *
     * @return Job
     */
    private function createTaskReportCompletionJob($taskId)
    {
        $resqueJobFactory = $this->container->get(JobFactory::class);

        $taskReportCompletionJob = $resqueJobFactory->create(self::QUEUE, [
            'id' => $taskId,
        ]);

        $taskReportCompletionJob->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $taskReportCompletionJob;
    }
}