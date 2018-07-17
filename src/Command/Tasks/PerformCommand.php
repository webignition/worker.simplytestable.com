<?php

namespace App\Command\Tasks;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Command\Task\PerformCommand as TaskPerformCommand;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\TaskService;
use App\Services\WorkerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PerformCommand extends AbstractTaskCollectionCommand
{
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
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     * @param TaskService $taskService
     * @param WorkerService $workerService
     * @param ResqueQueueService $resqueQueueService
     * @param string|null $name
     */
    public function __construct(
        EntityManagerInterface $entityManager,
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

        $performCommand = new TaskPerformCommand(
            $this->logger,
            $this->taskService,
            $this->workerService,
            $this->resqueQueueService
        );

        $this->executeForCollection($taskIds, $performCommand, $output);

        return 0;
    }
}
