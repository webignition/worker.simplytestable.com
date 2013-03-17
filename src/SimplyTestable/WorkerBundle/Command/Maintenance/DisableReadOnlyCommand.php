<?php
namespace SimplyTestable\WorkerBundle\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class DisableReadOnlyCommand extends BaseCommand
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
        if ($this->getWorkerService()->isActive()) {
            $output->writeln('Set state to active');
            return 0;
        }
        
        $output->writeln('Failed to set state to active');
        return 0;
    }     
}