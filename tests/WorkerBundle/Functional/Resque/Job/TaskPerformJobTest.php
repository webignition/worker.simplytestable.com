<?php

namespace Tests\WorkerBundle\Functional\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Task\PerformCommand;
use SimplyTestable\WorkerBundle\Resque\Job\TaskPerformJob;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Factory\TestTaskFactory;

class TaskPerformJobTest extends AbstractJobTest
{
    const QUEUE = 'task-perform';

    public function testRunWithInvalidTask()
    {
        $job = $this->createJob(
            ['id' => 1],
            self::QUEUE,
            $this->container->get(PerformCommand::class)
        );
        $this->assertInstanceOf(TaskPerformJob::class, $job);

        $returnCode = $job->run([]);

        $this->assertEquals(PerformCommand::RETURN_CODE_TASK_DOES_NOT_EXIST, $returnCode);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->container->get(WorkerService::class)->setReadOnly();
        $this->clearRedis();
        $task = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([]));

        $job = $this->createJob(
            ['id' => $task->getId(),],
            self::QUEUE,
            $this->container->get(PerformCommand::class)
        );

        $returnCode = $job->run([]);

        $this->assertEquals(PerformCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }
}
