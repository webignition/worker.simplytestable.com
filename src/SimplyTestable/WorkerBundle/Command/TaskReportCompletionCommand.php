<?php
namespace SimplyTestable\WorkerBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TaskReportCompletionCommand extends TaskCommand
{    
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -2;     
    const RETURN_CODE_UNKNOWN_ERROR = -3;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:reportcompletion')
            ->setDescription('Report back to the core application the completed status of a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to report')                
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
            ->setHelp(<<<EOF
Report back to the core application the completed status of a task
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasArgument('http-fixture-path')) {
            $httpClient = $this->getContainer()->get('simplytestable.services.httpClient');
            
            if ($httpClient instanceof \webignition\Http\Mock\Client\Client) {
                $httpClient->getStoredResponseList()->setFixturesPath($input->getArgument('http-fixture-path'));
            }            
        }
        
        $task = $this->getTaskService()->getById($input->getArgument('id'));
        if (is_null($task)) {
            $this->getContainer()->get('logger')->err("TaskReportCompletionCommand::execute: [".$input->getArgument('id')."] does not exist");            
            $output->writeln($input->getArgument('id')."] does not exist");
            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }
        
        if ($this->getWorkerService()->isMaintenanceReadOnly()) {            
            $output->writeln('Unable to report completion, worker application is in maintenance read-only mode');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }         
        
        $reportCompletionResult = $this->getTaskService()->reportCompletion($task);
        
        if ($reportCompletionResult === true) {
            $output->writeln('Reported task completion ['.$task->getId().']');
            
            /* @var $entityManager \Doctrine\ORM\EntityManager */        
            $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
            $entityManager->remove($task);
            $entityManager->remove($task->getOutput());
            $entityManager->flush();
            
            return 0;
        }
        
        if ($reportCompletionResult === false) {
            $output->writeln('Report completion failed, unknown error');
            return self::RETURN_CODE_UNKNOWN_ERROR;
        }
        
        if ($this->isHttpStatusCode($reportCompletionResult)) {
            $output->writeln('Report completion failed, HTTP response '.$reportCompletionResult);
        } else {
            $output->writeln('Report completion failed, CURL error '.$reportCompletionResult);
        }
        
        return $reportCompletionResult;  
    }
}