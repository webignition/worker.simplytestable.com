<?php
namespace SimplyTestable\WorkerBundle\Command\Tasks;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RequestIfEmptyCommand extends Command {

    const RETURN_CODE_OK = 0;

    protected function configure()
    {
        $this
            ->setName('simplytestable:tasks:requestifempty')
            ->setDescription('Pop a resque tasks-request job in the queue if the queue is empty')
            ->setHelp(<<<EOF
Request tasks to be assigned by the core application
EOF
        );
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        if ($this->getResqueQueueService()->isEmpty('tasks-request')) {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'tasks-request'
                )
            );
        }

        return self::RETURN_CODE_OK;
    }
}