<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;
use SimplyTestable\WorkerBundle\Resque\Job\Job;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;

class TasksRequestJobTest extends BaseSimplyTestableTestCase
{
    const QUEUE = 'tasks-request';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->getWorkerService()->setReadOnly();
        $this->clearRedis();

        $tasksRequestJob = $this->createTasksRequestJob();

        $returnCode = $tasksRequestJob->run([]);

        $this->assertEquals(
            RequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    /**
     * @param int $taskId
     *
     * @return Job
     */
    private function createTasksRequestJob()
    {
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $tasksRequestJob = $resqueJobFactory->create(self::QUEUE, []);

        $tasksRequestJob->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $tasksRequestJob;
    }
}
