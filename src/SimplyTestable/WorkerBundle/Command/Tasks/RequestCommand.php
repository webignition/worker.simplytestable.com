<?php
namespace SimplyTestable\WorkerBundle\Command\Tasks;

use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\WorkerBundle\Exception\Services\TasksService\RequestException;
use Symfony\Component\Console\Command\Command;

class RequestCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_FAILED = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    const RETURN_CODE_TASK_WORKLOAD_EXCEEDS_REQUEST_THRESHOLD = 3;

    const NAME = 'simplytestable:tasks:request';

    /**
     * @var TasksService
     */
    private $tasksService;

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
     * @param TasksService $tasksService
     * @param WorkerService $workerService
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param string|null $name
     */
    public function __construct(
        TasksService $tasksService,
        WorkerService $workerService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        $name = null
    ) {
        parent::__construct($name);

        $this->tasksService = $tasksService;
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
            ->setName(self::NAME)
            ->setDescription('Request tasks to be assigned by the core application')
            ->addArgument('limit', InputArgument::OPTIONAL, 'maximum number of tasks to request')
            ->setHelp('Request tasks to be assigned by the core application');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->workerService->isMaintenanceReadOnly()) {
            $this->requeue();

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        try {
            if ($this->tasksService->request($input->getArgument('limit'))) {
                return self::RETURN_CODE_OK;
            }

            $this->requeue();
            return self::RETURN_CODE_TASK_WORKLOAD_EXCEEDS_REQUEST_THRESHOLD;
        } catch (RequestException $requestException) {
        }

        $this->requeue();
        return self::RETURN_CODE_FAILED;
    }

    private function requeue()
    {
        if ($this->resqueQueueService->isEmpty('tasks-request')) {
            $this->resqueQueueService->enqueue(
                $this->resqueJobFactory->create(
                    'tasks-request'
                )
            );
        }
    }
}
