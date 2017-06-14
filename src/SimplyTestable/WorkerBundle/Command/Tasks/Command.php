<?php
namespace SimplyTestable\WorkerBundle\Command\Tasks;

use SimplyTestable\WorkerBundle\Command\BaseCommand;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactoryService;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TasksService;

abstract class Command extends BaseCommand
{
    /**
     * @return TasksService
     */
    protected function getTasksService()
    {
        return $this->getContainer()->get('simplytestable.services.tasksservice');
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
