<?php
namespace SimplyTestable\WorkerBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerActivateCommand extends BaseCommand
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1; 
    const RETURN_CODE_UNKNOWN_ERROR = -2;    
    const RETURN_CODE_FAILED_DUE_TO_WRONG_STATE = -3;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:worker:activate')
            ->setDescription('Activate this worker, making it known to all core application instance of which it is aware')
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        if ($input->hasArgument('http-fixture-path')) {
            $httpClient = $this->getContainer()->get('simplytestable.services.httpClient');
            
            if ($httpClient instanceof \webignition\Http\Mock\Client\Client) {
                $httpClient->getStoredResponseList()->setFixturesPath($input->getArgument('http-fixture-path'));
            }            
        }
        
        if ($this->getWorkerService()->isMaintenanceReadOnly()) {
            $output->writeln('Unable to activate, worker application is in maintenance read-only mode');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }
        
        $activationResult = $this->getWorkerService()->activate();        
        if ($activationResult === 0) {
            return 0;
        }
        
        if ($activationResult === 1) {
            $output->writeln('Activation failed, unknown error');
            return self::RETURN_CODE_UNKNOWN_ERROR;
        }
        
        if ($activationResult === 0) {            
            $output->writeln('Activation failed, worker application is not in the correct state (current state:'.$this->getWorkerService()->get()->getState().')');
            return self::RETURN_CODE_FAILED_DUE_TO_WRONG_STATE;            
        }
        
        if ($this->isHttpStatusCode($activationResult)) {
            $output->writeln('Activation failed, HTTP response '.$activationResult);
        } else {
            $output->writeln('Activation failed, CURL error '.$activationResult);
        } 
        
        return $activationResult;      
    }     
  
}