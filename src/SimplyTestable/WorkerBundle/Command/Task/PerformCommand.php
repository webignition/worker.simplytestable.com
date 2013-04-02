<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PerformCommand extends Command
{ 
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -2;
    const RETURN_CODE_FAILED_DUE_TO_WRONG_STATE = -3;
    const RETURN_CODE_FAILED_NO_TASK_DRIVER_FOUND = -4;
    const RETURN_CODE_UNKNOWN_ERROR = -5;
    
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
            ->setHelp(<<<EOF
Start a task
EOF
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $task = $this->getTaskService()->getById($input->getArgument('id'));      
        
        if (is_null($task)) {
            $this->getContainer()->get('logger')->err("TaskPerformCommand::execute: [".$input->getArgument('id')."] does not exist");            
            $output->writeln('Unable to execute, task '.$input->getArgument('id').' does not exist');
            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }       
        
        $this->getContainer()->get('logger')->info("TaskPerformCommand::execute: [".$task->getId()."] [".$task->getState()->getName()."]");        

        if ($this->getWorkerService()->isMaintenanceReadOnly()) {            
            $output->writeln('Unable to perform task, worker application is in maintenance read-only mode');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }
        
        $performResult = $this->getTaskService()->perform($task);
        
        if ($performResult === 0) {
            $this->getContainer()->get('simplytestable.services.resqueQueueService')->add(
                'task-report-completion',
                array(
                    'id' => $task->getId()
                )                
            );        
            
            $output->writeln('Performed ['.$task->getId().']');
            $this->getContainer()->get('logger')->info('TaskPerformCommand::Performed ['.$task->getId().'] ['.$task->getState().'] ['.($task->hasOutput() ? 'has output' : 'no output').']');

            return 0; 
        }
        
        if ($performResult === 1) {
            $output->writeln('Task perform failed, task is in wrong state (currently:'.$task->getState().')');
            return self::RETURN_CODE_FAILED_DUE_TO_WRONG_STATE;
        }
        
        if ($performResult === 2) {
            $output->writeln('Task perform failed, no driver found for task type ['.$task->getType()->getName().']');
            return self::RETURN_CODE_FAILED_NO_TASK_DRIVER_FOUND;
        }
        
        $output->writeln('Task perform failed, unknown error');
        return self::RETURN_CODE_UNKNOWN_ERROR;       
    }    

}