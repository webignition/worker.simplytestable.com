<?php
namespace SimplyTestable\WorkerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerActivateCommand extends ContainerAwareCommand
{    
    /**
     *
     * @var string
     */
    //private $httpFixturePath;    
    
    
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
            $httpClient->getStoredResponseList()->setFixturesPath($input->getArgument('http-fixture-path'));
        }
        
        if ($this->getWorkerService()->activate() === false) {
            throw new \LogicException('Worker activation failed, check log for details');
        }        
    } 
    
    /**
     *
     * @return SimplyTestable\WorkerBundle\Services\WorkerService
     */
    private function getWorkerService() {
        return $this->getContainer()->get('simplytestable.services.workerservice');
    }
}