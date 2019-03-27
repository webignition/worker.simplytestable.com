<?php

namespace App\Command\Maintenance;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Repository\TaskRepository;
use App\Resque\Job\TaskPerformJob;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\TaskService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\SymfonyConsole\TypedInput\TypedInput;

class RequeueInProgressTasksCommand extends Command
{
    const DEFAULT_AGE_IN_HOURS = 1;

    private $entityManager;
    private $taskService;
    private $resqueQueueService;
    private $taskRepository;

    public function __construct(
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        ResqueQueueService $resqueQueueService,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->taskService = $taskService;
        $this->resqueQueueService = $resqueQueueService;

        $this->taskRepository = $taskRepository;
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
        $typedInput = new TypedInput($input);

        $isDryRun = $typedInput->getBooleanOption('dry-run');
        $ageInHours = $typedInput->getIntegerOption('age-in-hours');
        $ageInHours = $ageInHours > 0 ? $ageInHours : self::DEFAULT_AGE_IN_HOURS;

        if ($isDryRun) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }

        $output->writeln('Using age-in-hours: <info>'.$ageInHours.'</info>');

        $startDateTime = new \DateTime('-'.$ageInHours.' hour');
        $taskIds = $this->taskRepository->getUnfinishedIdsByMaxStartDate($startDateTime);

        $output->writeln(
            'Tasks started more than '.$ageInHours.' hours ago: <info>'.count($taskIds).'</info>'
        );
        $output->writeln('');

        $processedTaskCount = 0;

        foreach ($taskIds as $taskId) {
            $processedTaskCount++;
            $output->writeln('Processing task '.$taskId.' ('.$processedTaskCount.' of '.count($taskIds).')');

            $inProgressTask = $this->taskService->getById($taskId);
            $inProgressTask->setState(Task::STATE_QUEUED);

            if ($isDryRun) {
                $this->entityManager->detach($inProgressTask);
            } else {
                $this->entityManager->persist($inProgressTask);
                $this->entityManager->flush();

                $this->resqueQueueService->enqueue(new TaskPerformJob(['id' => $inProgressTask->getId()]));
            }
        }

        $output->writeln('');
    }
}
