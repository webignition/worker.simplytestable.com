<?php

namespace App\Command\Task;

use Psr\Log\LoggerInterface;
use App\Resque\Job\TaskPerformJob;
use App\Resque\Job\TaskReportCompletionJob;
use App\Resque\Job\TasksRequestJob;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\TaskService;
use App\Services\WorkerService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class PerformCommand extends Command
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -2;
    const RETURN_CODE_TASK_SERVICE_RAISED_EXCEPTION = -6;

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
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @param LoggerInterface $logger
     * @param TaskService $taskService
     * @param WorkerService $workerService
     * @param ResqueQueueService $resqueQueueService
     * @param string|null $name
     */
    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        WorkerService $workerService,
        ResqueQueueService $resqueQueueService,
        $name = null
    ) {
        parent::__construct($name);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->workerService = $workerService;
        $this->resqueQueueService = $resqueQueueService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:perform')
            ->setDescription('Start a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to start')
            ->setHelp('Start a task');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $taskId = $input->getArgument('id');

        $task = $this->taskService->getById($taskId);

        if (is_null($task)) {
            $this->logger->error(sprintf(
                'TaskPerformCommand::execute: [%s] does not exist',
                $taskId
            ));
            $output->writeln('Unable to execute, task '.$input->getArgument('id').' does not exist');

            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }

        $this->logger->info("TaskPerformCommand::execute: [".$task->getId()."] [".$task->getState()->getName()."]");

        if ($this->workerService->isMaintenanceReadOnly()) {
            if (!$this->resqueQueueService->contains('task-perform', ['id' => $task->getId()])) {
                $this->resqueQueueService->enqueue(new TaskPerformJob(['id' => $task->getId()]));
            }

            $output->writeln('Unable to perform task, worker application is in maintenance read-only mode');

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $this->taskService->perform($task);

        if ($this->resqueQueueService->isEmpty('tasks-request')) {
            $this->resqueQueueService->enqueue(new TasksRequestJob());
        }

        $this->resqueQueueService->enqueue(new TaskReportCompletionJob(['id' => $task->getId()]));

        $output->writeln('Performed ['.$task->getId().']');
        $this->logger->info(sprintf(
            'TaskPerformCommand::Performed [%d] [%s] [%s]',
            $task->getId(),
            $task->getState(),
            ($task->hasOutput() ? 'has output' : 'no output')
        ));

        return 0;
    }
}
