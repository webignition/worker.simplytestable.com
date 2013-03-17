<?php
namespace SimplyTestable\WorkerBundle\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class EnableReadOnlyCommand extends BaseCommand
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
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getWorkerService()->setReadOnly();        
        if ($this->getWorkerService()->isMaintenanceReadOnly()) {
            $output->writeln('Set state to maintenance-read-only');
            return 0;
        }
        
        $output->writeln('Failed to set state to maintenance-read-only');
        return 0;
    }     
}