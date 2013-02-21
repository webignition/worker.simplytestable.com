<?php
namespace SimplyTestable\WorkerBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class MaintenanceEnableReadOnlyCommand extends BaseCommand
{ 
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:enable-read-only')
            ->setDescription('Enable read-only mode')
            ->setHelp(<<<EOF
Enable read-only mode
EOF
        );
    }    
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getWorkerService()->setReadOnly();        
    }     
}