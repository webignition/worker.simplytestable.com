<?php

namespace App\Command\Tasks;

use App\Command\Task\ReportCompletionCommand as TaskReportCompletionCommand;
use App\Repository\TaskRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReportCompletionCommand extends AbstractTaskCollectionCommand
{
    /**
     * @var TaskRepository
     */
    private $taskRepository;

    private $taskReportCompletionCommand;

    public function __construct(
        TaskRepository $taskRepository,
        TaskReportCompletionCommand $taskReportCompletionCommand
    ) {
        parent::__construct(null);

        $this->taskReportCompletionCommand = $taskReportCompletionCommand;
        $this->taskRepository = $taskRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:tasks:reportcompletion')
            ->setDescription('Report completion for all jobs finished')
            ->setHelp('Report completion for all jobs finished');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $taskIds = $this->taskRepository->getIdsWithOutput();
        $output->writeln(count($taskIds).' tasks with output ready to report completion');

        $this->executeForCollection($taskIds, $this->taskReportCompletionCommand, $output);

        return 0;
    }
}
