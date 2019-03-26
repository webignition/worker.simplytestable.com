<?php

namespace App\Command\Task;

use App\Services\TaskPreparer;
use Psr\Log\LoggerInterface;
use App\Services\TaskService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrepareCommand extends AbstractTaskCommand
{
    private $taskPreparer;

    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        TaskPreparer $taskPreparer
    ) {
        parent::__construct($logger, $taskService);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->taskPreparer = $taskPreparer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:prepare')
            ->setDescription('Prepare a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to prepare');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $parentReturnCode = parent::execute($input, $output);
        if (self::RETURN_CODE_OK !== $parentReturnCode) {
            return $parentReturnCode;
        }

        $this->taskPreparer->prepare($this->task);

        $output->writeln('Prepare started [' . $this->task->getId() . ']');

        return 0;
    }
}
