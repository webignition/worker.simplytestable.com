<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use SimplyTestable\WorkerBundle\Command\BaseCommand;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactoryService;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;

abstract class Command extends BaseCommand
{
    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->getContainer()->get('simplytestable.services.taskservice');
    }

    /**
     * @return QueueService
     */
    protected function getResqueQueueService()
    {
        return $this->getContainer()->get('simplytestable.services.resque.queueservice');
    }

    /**
     * @return JobFactoryService
     */
    protected function getResqueJobFactoryService()
    {
        return $this->getContainer()->get('simplytestable.services.resque.jobFactoryService');
    }
}
