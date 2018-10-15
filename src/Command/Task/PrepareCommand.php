<?php

namespace App\Command\Task;

use App\Resque\Job\TaskPrepareJob;
use App\Services\TaskPreparer;
use Psr\Log\LoggerInterface;
use App\Resque\Job\TaskPerformJob;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\TaskService;
use App\Services\WorkerService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrepareCommand extends AbstractTaskCommand
{
    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var TaskPreparer
     */
    private $taskPreparer;

    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        WorkerService $workerService,
        ResqueQueueService $resqueQueueService,
        TaskPreparer $taskPreparer
    ) {
        parent::__construct($logger, $taskService, $workerService);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->resqueQueueService = $resqueQueueService;
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

    protected function handleWorkerMaintenanceReadOnlyMode()
    {
        $taskId = $this->task->getId();

        if (!$this->resqueQueueService->contains('task-prepare', ['id' => $taskId])) {
            $this->resqueQueueService->enqueue(new TaskPrepareJob(['id' => $taskId]));
        }
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
        $this->taskPreparer->prepare($this->task);

        $this->resqueQueueService->enqueue(new TaskPerformJob(['id' => $taskId]));

        $output->writeln('Prepared [' . $taskId . ']');

        return 0;
    }
}
