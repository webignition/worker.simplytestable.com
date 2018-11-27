<?php

namespace App\Command\Task;

use App\Services\TaskPerformer;
use Psr\Log\LoggerInterface;
use App\Resque\Job\TaskReportCompletionJob;
use App\Resque\Job\TasksRequestJob;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\TaskService;
use App\Services\WorkerService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PerformCommand extends AbstractTaskCommand
{
    const RETURN_CODE_TASK_SERVICE_RAISED_EXCEPTION = -6;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var TaskPerformer
     */
    private $taskPerformanceService;

    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        WorkerService $workerService,
        ResqueQueueService $resqueQueueService,
        TaskPerformer $taskPerformanceService
    ) {
        parent::__construct($logger, $taskService, $workerService);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->resqueQueueService = $resqueQueueService;
        $this->taskPerformanceService = $taskPerformanceService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:perform')
            ->setDescription('Start a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to start');
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

        $taskId = $this->task->getId();
        $this->taskPerformanceService->perform($this->task);

        if ($this->resqueQueueService->isEmpty('tasks-request')) {
            $this->resqueQueueService->enqueue(new TasksRequestJob());
        }

        $this->resqueQueueService->enqueue(new TaskReportCompletionJob(['id' => $taskId]));

        $output->writeln('Performed [' . $taskId . ']');

        return 0;
    }
}
