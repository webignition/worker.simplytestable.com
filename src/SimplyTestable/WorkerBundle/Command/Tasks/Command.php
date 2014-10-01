<?php
namespace SimplyTestable\WorkerBundle\Command\Tasks;

use SimplyTestable\WorkerBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


abstract class Command extends BaseCommand
{    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TasksService
     */
    protected function getTasksService() {
        return $this->getContainer()->get('simplytestable.services.tasksservice');
    }


    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\Resque\QueueService
     */
    protected function getResqueQueueService() {
        return $this->getContainer()->get('simplytestable.services.resque.queueservice');
    }


    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\Resque\JobFactoryService
     */
    protected function getResqueJobFactoryService() {
        return $this->getContainer()->get('simplytestable.services.resque.jobFactoryService');
    }     
}