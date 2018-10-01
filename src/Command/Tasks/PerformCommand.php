<?php

namespace App\Command\Tasks;

use App\Command\Task\PerformCommand as TaskPerformCommand;
use App\Services\TaskService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PerformCommand extends AbstractTaskCollectionCommand
{
    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var TaskPerformCommand
     */
    private $taskPerformCommand;

    public function __construct(TaskService $taskService, TaskPerformCommand $taskPerformCommand)
    {
        parent::__construct(null);

        $this->taskService = $taskService;
        $this->taskPerformCommand = $taskPerformCommand;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:tasks:perform')
            ->setDescription('Perform all jobs queued')
            ->setHelp('Perform all jobs queued');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $taskIds = $this->taskService->getQueuedTaskIds();
        $output->writeln(count($taskIds).' queued tasks ready to be performed');

        $this->executeForCollection($taskIds, $this->taskPerformCommand, $output);

        return 0;
    }
}
