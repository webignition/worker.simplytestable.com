<?php
namespace SimplyTestable\WorkerBundle\Command\Tasks;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RequestIfEmptyCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const QUEUE_NAME = 'tasks-request';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:tasks:requestifempty')
            ->setDescription('Pop a resque tasks-request job in the queue if the queue is empty')
            ->setHelp('Request tasks to be assigned by the core application');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getResqueQueueService()->isEmpty(self::QUEUE_NAME)) {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    self::QUEUE_NAME
                )
            );
        }

        return self::RETURN_CODE_OK;
    }
}
