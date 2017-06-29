<?php
namespace SimplyTestable\WorkerBundle\Command\Tasks;

use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Command\Task\PerformCommand as TaskPerformCommand;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
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
     * @var ResqueJobFactory
     */
    private $resqueJobFactory;

    /**
     * @param LoggerInterface $logger
     * @param TaskService $taskService
     * @param WorkerService $workerService
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param string|null $name
     */
    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        WorkerService $workerService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        $name = null
    ) {
        parent::__construct($name);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->workerService = $workerService;
        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;
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
        $taskIds = $this->taskService->getEntityRepository()->getIdsByState($this->taskService->getQueuedState());
        $output->writeln(count($taskIds).' queued tasks ready to be performed');

        $performCommand = new TaskPerformCommand(
            $this->logger,
            $this->taskService,
            $this->workerService,
            $this->resqueQueueService,
            $this->resqueJobFactory
        );

        $this->executeForCollection($taskIds, $performCommand, $output);

        return 0;
    }
}