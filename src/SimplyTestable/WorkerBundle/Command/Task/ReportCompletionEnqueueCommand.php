<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class ReportCompletionEnqueueCommand extends Command
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
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param string|null $name
     */
    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        $name = null
    ) {
        parent::__construct($name);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:reportcompletion:enqueue')
            ->setDescription('Enqueue task report completion jobs for tasks waiting to be report back completion state')
            ->setHelp('Enqueue task report completion jobs for tasks waiting to be report back completion state');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $taskIds = $this->taskService->getEntityRepository()->getIdsWithOutput();

        $this->logger->info('TaskReportCompletionEnqueueCommand::initialise');
        $this->logger->info(
            'TaskReportCompletionEnqueueCommand::'.count($taskIds).' completed tasks ready to be enqueued'
        );
        $output->writeln(count($taskIds).' completed tasks ready to be enqueued');

        foreach ($taskIds as $taskId) {
            if ($this->resqueQueueService->contains('task-report-completion', array('id' => $taskId))) {
                $output->writeln('Task ['.$taskId.'] is already enqueued');
                $this->logger->info('TaskReportCompletionEnqueueCommand::Task ['.$taskId.'] is already enqueued');
            } else {
                $output->writeln('Enqueuing task ['.$taskId.']');
                $this->logger->info('TaskReportCompletionEnqueueCommand::Enqueuing task ['.$taskId.']');

                $this->resqueQueueService->enqueue(
                    $this->resqueJobFactory->create(
                        'task-report-completion',
                        ['id' => $taskId]
                    )
                );
            }
        }

        return 0;
    }
}
