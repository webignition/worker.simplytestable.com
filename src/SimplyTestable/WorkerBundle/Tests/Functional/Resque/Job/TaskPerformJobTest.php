<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Task\PerformCommand;
use SimplyTestable\WorkerBundle\Resque\Job\Job;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;

class TaskPerformJobTest extends BaseSimplyTestableTestCase
{
    const QUEUE = 'task-perform';

    public function testRunWithInvalidTask()
    {
        $taskPerformJob = $this->createTaskPerformJob(-1);

        $returnCode = $taskPerformJob->run([]);

        $this->assertEquals(PerformCommand::RETURN_CODE_TASK_DOES_NOT_EXIST, $returnCode);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->getWorkerService()->setReadOnly();
        $this->clearRedis();
        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));

        $taskPerformJob = $this->createTaskPerformJob($task->getId());

        $returnCode = $taskPerformJob->run([]);

        $this->assertEquals(PerformCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }

    /**
     * @param int $taskId
     *
     * @return Job
     */
    private function createTaskPerformJob($taskId)
    {
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $taskPerformJob = $resqueJobFactory->create(self::QUEUE, [
            'id' => $taskId,
        ]);

        $taskPerformJob->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $taskPerformJob;
    }
}
