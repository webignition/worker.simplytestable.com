<?php
namespace App\Command\Task;

use Psr\Log\LoggerInterface;
use App\Services\TaskService;
use App\Services\WorkerService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class ReportCompletionCommand extends Command
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -2;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var WorkerService
     */
    private $workerService;

    /**
     * @param LoggerInterface $logger
     * @param TaskService $taskService
     * @param WorkerService $workerService
     * @param string|null $name
     */
    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        WorkerService $workerService,
        $name = null
    ) {
        parent::__construct($name);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->workerService = $workerService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:reportcompletion')
            ->setDescription('Report back to the core application the completed status of a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to report')
            ->setHelp('Report back to the core application the completed status of a task');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $worker = $this->workerService->get();

        if ($worker->isMaintenanceReadOnly()) {
            $output->writeln('Unable to report completion, worker application is in maintenance read-only mode');

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $taskId = $input->getArgument('id');

        $task = $this->taskService->getById($taskId);
        if (is_null($task)) {
            $this->logger->error(sprintf(
                'TaskReportCompletionCommand::execute: [%s] does not exist',
                $taskId
            ));
            $output->writeln(sprintf('[%s] does not exist', $taskId));

            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }

        $reportCompletionResult = $this->taskService->reportCompletion($task);

        if ($reportCompletionResult === true) {
            $output->writeln('Reported task completion [' .$task->getId().']');

            return 0;
        }

        if (strlen($reportCompletionResult) === 3) {
            $output->writeln('Report completion failed, HTTP response '.$reportCompletionResult);
        } else {
            $output->writeln('Report completion failed, CURL error '.$reportCompletionResult);
        }

        return $reportCompletionResult;
    }
}
