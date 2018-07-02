<?php

namespace SimplyTestable\WorkerBundle\Command\Task;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Repository\TaskRepository;
use SimplyTestable\WorkerBundle\Resque\Job\TaskReportCompletionJob;
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
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     * @param TaskService $taskService
     * @param ResqueQueueService $resqueQueueService
     * @param string|null $name
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        TaskService $taskService,
        ResqueQueueService $resqueQueueService,
        $name = null
    ) {
        parent::__construct($name);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->resqueQueueService = $resqueQueueService;

        $this->taskRepository = $entityManager->getRepository(Task::class);
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
        $taskIds = $this->taskRepository->getIdsWithOutput();

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

                $this->resqueQueueService->enqueue(new TaskReportCompletionJob(['id' => $taskId]));
            }
        }

        return 0;
    }
}
