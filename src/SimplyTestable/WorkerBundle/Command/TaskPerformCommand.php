<?php
namespace SimplyTestable\WorkerBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TaskPerformCommand extends TaskCommand
{ 
    const RETURN_CODE_TASK_DOES_NOT_EXIST = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    
    /**
     *
     * @var string
     */
    //private $httpFixturePath;    
    
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:perform')
            ->setDescription('Start a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to start')                
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
            ->setHelp(<<<EOF
Start a task
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        $task = $this->getTaskService()->getById($input->getArgument('id'));
        if (is_null($task)) {
            $this->getContainer()->get('logger')->err("TaskPerformCommand::execute: [".$input->getArgument('id')."] does not exist");            
            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }
        
        $this->getContainer()->get('logger')->info("TaskPerformCommand::execute: [".$task->getId()."] [".$task->getState()->getName()."]");        

        if ($this->getWorkerService()->isMaintenanceReadOnly()) {
            $this->getContainer()->get('simplytestable.services.resqueQueueService')->add(
                'task-perform',
                array(
                    'id' => $task->getId()
                )                
            );
            
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }        
        
        if ($input->hasArgument('http-fixture-path')) {
            $httpClient = $this->getContainer()->get('simplytestable.services.httpClient');
            
            if ($httpClient instanceof \webignition\Http\Mock\Client\Client) {
                $httpClient->getStoredResponseList()->setFixturesPath($input->getArgument('http-fixture-path'));
            }            
        }        
        
        if ($this->getTaskService()->perform($task) === false) {
            throw new \LogicException('Task execution failed, check log for details');
        }
        
        $this->getContainer()->get('simplytestable.services.resqueQueueService')->add(
            'task-report-completion',
            array(
                'id' => $task->getId()
            )                
        );        
        
        return 0;        
    }
}