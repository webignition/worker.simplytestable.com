<?php

namespace SimplyTestable\WorkerBundle\Command\Task;

use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Resque\Job\TaskPerformJob;
use SimplyTestable\WorkerBundle\Resque\Job\TaskReportCompletionJob;
use SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class PerformCommand extends Command
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -2;
    const RETURN_CODE_UNKNOWN_ERROR = -5;
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

        try {
            $performResult = $this->taskService->perform($task);
        } catch (\Exception $e) {
            $this->logger->error('TaskPerformCommand: Exception: taskId: [' . $task->getId() . ']');
            $this->logger->error('TaskPerformCommand: Exception: exception class: [' . get_class($e) . ']');
            $this->logger->error('TaskPerformCommand: Exception: exception code: [' . $e->getCode() . ']');
            $this->logger->error('TaskPerformCommand: Exception: exception msg: [' . $e->getMessage() . ']');

            return self::RETURN_CODE_TASK_SERVICE_RAISED_EXCEPTION;
        }

        if ($this->resqueQueueService->isEmpty('tasks-request')) {
            $this->resqueQueueService->enqueue(new TasksRequestJob());
        }

        if ($performResult === 0) {
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

        $output->writeln('Task perform failed, unknown error');

        return self::RETURN_CODE_UNKNOWN_ERROR;
    }
}
