<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use SimplyTestable\WorkerBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


abstract class Command extends BaseCommand
{    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TaskService
     */
    protected function getTaskService() {
        return $this->getContainer()->get('simplytestable.services.taskservice');
    }  
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\ResqueQueueService
     */
    protected function getResqueQueueService() {
        return $this->getContainer()->get('simplytestable.services.resquequeueservice');
    }     
}