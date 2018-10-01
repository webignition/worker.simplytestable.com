<?php

namespace App\Command\Task;

use App\Entity\Task\Task;
use App\Services\TaskService;
use App\Services\WorkerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractTaskCommand extends Command
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -2;
    const RETURN_CODE_OK = 0;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TaskService
     */
    protected $taskService;

    /**
     * @var WorkerService
     */
    protected $workerService;

    /**
     * @var Task
     */
    protected $task;

    public function __construct(LoggerInterface $logger, TaskService $taskService, WorkerService $workerService)
    {
        parent::__construct(null);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->workerService = $workerService;
    }

    abstract protected function handleWorkerMaintenanceReadOnlyMode();

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $taskId = $input->getArgument('id');

        $this->task = $this->taskService->getById($taskId);
        if (empty($this->task)) {
            $taskIdDoesNotExistMessage = sprintf('[%s] does not exist', $taskId);

            $this->logger->error(sprintf(
                '%s::execute [%s]: %s',
                $this->getName(),
                $taskId,
                $taskIdDoesNotExistMessage
            ));
            $output->writeln($taskIdDoesNotExistMessage);

            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }

        $worker = $this->workerService->get();
        if ($worker->isMaintenanceReadOnly()) {
            $this->logger->error(sprintf(
                '%s::execute [%s]: worker application is in maintenance read-only mode',
                $this->getName(),
                $this->task->getId()
            ));

            $this->handleWorkerMaintenanceReadOnlyMode();

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        return self::RETURN_CODE_OK;
    }
}
