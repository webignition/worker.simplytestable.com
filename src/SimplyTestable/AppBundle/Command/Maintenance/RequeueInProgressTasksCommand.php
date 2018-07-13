<?php

namespace SimplyTestable\AppBundle\Command\Maintenance;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\AppBundle\Entity\Task\Task;
use SimplyTestable\AppBundle\Repository\TaskRepository;
use SimplyTestable\AppBundle\Resque\Job\TaskPerformJob;
use SimplyTestable\AppBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\AppBundle\Services\TaskService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RequeueInProgressTasksCommand extends Command
{
    const DEFAULT_AGE_IN_HOURS = 1;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

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
     * @var InputInterface
     */
    private $input;

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

        $this->entityManager = $entityManager;
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
            ->setName('simplytestable:maintenance:requeue-in-progress-tasks')
            ->setDescription('Requeue tasks in progress that are started more than X hours ago')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'desc')
            ->addOption('age-in-hours', null, InputOption::VALUE_OPTIONAL, 'desc', self::DEFAULT_AGE_IN_HOURS)
            ->setHelp('Requeue tasks in progress that are started prior to a given date');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        if ($this->isDryRun()) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }

        $output->writeln('Using age-in-hours: <info>'.$this->getAgeInHours().'</info>');

        $startDateTime = new \DateTime('-'.$this->getAgeInHours().' hour');
        $taskIds = $this->taskRepository->getUnfinishedIdsByMaxStartDate($startDateTime);

        $output->writeln(
            'Tasks started more than '.$this->getAgeInHours().' hours ago: <info>'.count($taskIds).'</info>'
        );
        $output->writeln('');

        $processedTaskCount = 0;

        foreach ($taskIds as $taskId) {
            $processedTaskCount++;
            $output->writeln('Processing task '.$taskId.' ('.$processedTaskCount.' of '.count($taskIds).')');

            $inProgressTask = $this->taskService->getById($taskId);
            $this->taskService->setQueued($inProgressTask);

            if ($this->isDryRun()) {
                $this->entityManager->detach($inProgressTask);
            } else {
                $this->entityManager->persist($inProgressTask);
                $this->entityManager->flush();

                $this->resqueQueueService->enqueue(new TaskPerformJob(['id' => $inProgressTask->getId()]));
            }
        }

        $output->writeln('');
    }

    /**
     * @return boolean
     */
    private function isDryRun()
    {
        return $this->input->getOption('dry-run') !== false;
    }

    /**
     * @return int
     */
    private function getAgeInHours()
    {
        $age = $this->input->getOption('age-in-hours');
        if (!is_int($age) || $age < self::DEFAULT_AGE_IN_HOURS) {
            $age = self::DEFAULT_AGE_IN_HOURS;
        }

        return $age;
    }
}
