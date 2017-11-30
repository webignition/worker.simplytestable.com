<?php

namespace Tests\WorkerBundle\Functional\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;
use SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class TasksRequestJobTest extends AbstractJobTest
{
    const QUEUE = 'tasks-request';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->container->get(WorkerService::class)->setReadOnly();
        $this->clearRedis();

        $job = $this->createJob(
            [],
            self::QUEUE
        );
        $this->assertInstanceOf(TasksRequestJob::class, $job);

        $returnCode = $job->run([]);

        $this->assertEquals(RequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }
}
