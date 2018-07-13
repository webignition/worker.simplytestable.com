<?php

namespace AppBundle\Command\Task;

use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Resque\Job\TaskPerformJob;
use AppBundle\Services\Resque\QueueService as ResqueQueueService;
use AppBundle\Services\TaskService;
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
     * @param EntityManagerInterface $entityManager
     * @param TaskService $taskService
     * @param ResqueQueueService $resqueQueueService
     * @param string|null $name
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        ResqueQueueService $resqueQueueService,
        $name = null
    ) {
        parent::__construct($name);

        $this->taskService = $taskService;
        $this->resqueQueueService = $resqueQueueService;
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
     *
     * @return int|null
     *
     * @throws \CredisException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $queuedTaskIds = $this->taskService->getQueuedTaskIds();

        $output->writeln(count($queuedTaskIds).' queued tasks ready to be enqueued');

        foreach ($queuedTaskIds as $taskId) {
            if ($this->resqueQueueService->contains('task-perform', array('id' => $taskId))) {
                $output->writeln('Task ['.$taskId.'] is already enqueued');
            } else {
                $output->writeln('Enqueuing task ['.$taskId.']');
                $this->resqueQueueService->enqueue(new TaskPerformJob(['id' => $taskId]));
            }
        }

        return 0;
    }
}
