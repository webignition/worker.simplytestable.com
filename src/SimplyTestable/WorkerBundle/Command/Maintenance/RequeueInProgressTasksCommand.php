<?php
namespace SimplyTestable\WorkerBundle\Command\Maintenance;

use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RequeueInProgressTasksCommand extends Command
{
    const DEFAULT_AGE_IN_HOURS = 1;

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
     * @var InputInterface
     */
    private $input;

    /**
     * @param TaskService $taskService
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param string|null $name
     */
    public function __construct(
        TaskService $taskService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        $name = null
    ) {
        parent::__construct($name);

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
        $taskIds = $this->taskService->getEntityRepository()->getUnfinishedIdsByMaxStartDate($startDateTime);

        $output->writeln(
            'Tasks started more than '.$this->getAgeInHours().' hours ago: <info>'.count($taskIds).'</info>'
        );
        $output->writeln('');

        $processedTaskCount = 0;

        foreach ($taskIds as $taskId) {
            $processedTaskCount++;
            $output->writeln('Processing task '.$taskId.' ('.$processedTaskCount.' of '.count($taskIds).')');

            $inProgressTask = $this->taskService->getById($taskId);
            $inProgressTask->setState($this->taskService->getQueuedState());

            if ($this->isDryRun()) {
                $this->taskService->getEntityManager()->detach($inProgressTask);
            } else {
                $this->taskService->getEntityManager()->persist($inProgressTask);
                $this->taskService->getEntityManager()->flush();

                $this->resqueQueueService->enqueue(
                    $this->resqueJobFactory->create(
                        'task-perform',
                        ['id' => $inProgressTask->getId()]
                    )
                );
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
