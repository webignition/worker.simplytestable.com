<?php
namespace SimplyTestable\WorkerBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class MaintenanceDisableReadOnlyCommand extends BaseCommand
{ 
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:disable-read-only')
            ->setDescription('Disable read-only mode')
            ->setHelp(<<<EOF
Disable read-only mode
EOF
        );
    }    
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getWorkerService()->clearReadOnly();        
        return 0;
    }     
}