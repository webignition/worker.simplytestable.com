<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Repository\TaskRepository;
use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class PerformEnqueueCommand extends Command
{
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
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TaskService $taskService
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param string|null $name
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        $name = null
    ) {
        parent::__construct($name);

        $this->taskService = $taskService;
        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;

        $this->taskRepository = $entityManager->getRepository(Task::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:perform:enqueue')
            ->setDescription('Enqueue task perform jobs for tasks waiting to be performed')
            ->setHelp('Enqueue task perform jobs for tasks waiting to be performed');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $queuedTaskIds = $this->taskRepository->getIdsByState(
            $this->taskService->getQueuedState()
        );
        $output->writeln(count($queuedTaskIds).' queued tasks ready to be enqueued');

        foreach ($queuedTaskIds as $taskId) {
            if ($this->resqueQueueService->contains('task-perform', array('id' => $taskId))) {
                $output->writeln('Task ['.$taskId.'] is already enqueued');
            } else {
                $output->writeln('Enqueuing task ['.$taskId.']');

                $this->resqueQueueService->enqueue(
                    $this->resqueJobFactory->create(
                        'task-perform',
                        ['id' => $taskId]
                    )
                );
            }
        }

        return 0;
    }
}
