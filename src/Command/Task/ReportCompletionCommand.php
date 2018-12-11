<?php

namespace App\Command\Task;

use App\Services\TaskCompletionReporter;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use App\Services\TaskService;
use App\Services\WorkerService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReportCompletionCommand extends AbstractTaskCommand
{
    const RETURN_CODE_FAILED = 1;

    /**
     * @var TaskCompletionReporter
     */
    private $taskCompletionReporter;

    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        WorkerService $workerService,
        TaskCompletionReporter $taskCompletionReporter
    ) {
        parent::__construct($logger, $taskService, $workerService);

        $this->taskCompletionReporter = $taskCompletionReporter;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:reportcompletion')
            ->setDescription('Report back to the core application the completed status of a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to report');
    }

    /**
     * {@inheritdoc}
     *
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $parentReturnCode = parent::execute($input, $output);
        if (self::RETURN_CODE_OK !== $parentReturnCode) {
            return $parentReturnCode;
        }

        $reportCompletionResult = $this->taskCompletionReporter->reportCompletion($this->task);

        if ($reportCompletionResult === true) {
            $output->writeln('Reported task completion [' . $this->task->getId() . ']');

            return 0;
        }

        $output->writeln('Report completion failed, see log for details ');

        return self::RETURN_CODE_FAILED;
    }
}
