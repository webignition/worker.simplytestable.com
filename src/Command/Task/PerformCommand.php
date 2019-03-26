<?php

namespace App\Command\Task;

use App\Services\TaskPerformer;
use Psr\Log\LoggerInterface;
use App\Resque\Job\TasksRequestJob;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\TaskService;
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
    private $taskPerformer;

    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        ResqueQueueService $resqueQueueService,
        TaskPerformer $taskPerformer
    ) {
        parent::__construct($logger, $taskService);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->resqueQueueService = $resqueQueueService;
        $this->taskPerformer = $taskPerformer;
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

        $this->taskPerformer->perform($this->task);

        if ($this->resqueQueueService->isEmpty('tasks-request')) {
            $this->resqueQueueService->enqueue(new TasksRequestJob());
        }

        $output->writeln('Performed [' . $this->task->getId() . ']');

        return 0;
    }
}
