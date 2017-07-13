<?php

namespace Tests\WorkerBundle\Functional\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;
use SimplyTestable\WorkerBundle\Resque\Job\Job;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Factory\TaskFactory;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class TasksRequestJobTest extends BaseSimplyTestableTestCase
{
    const QUEUE = 'tasks-request';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->container->get(WorkerService::class)->setReadOnly();
        $this->clearRedis();

        $tasksRequestJob = $this->createTasksRequestJob();

        $returnCode = $tasksRequestJob->run([]);

        $this->assertEquals(
            RequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    /**
     * @return Job
     */
    private function createTasksRequestJob()
    {
        $resqueJobFactory = $this->container->get(JobFactory::class);

        $tasksRequestJob = $resqueJobFactory->create(self::QUEUE, []);

        $tasksRequestJob->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $tasksRequestJob;
    }
}
