<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactoryService as ResqueJobFactoryService;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class PerformAllCommand extends Command
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
     * @var ResqueJobFactoryService
     */
    private $resqueJobFactoryService;

    /**
     * @param LoggerInterface $logger
     * @param TaskService $taskService
     * @param WorkerService $workerService
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactoryService $resqueJobFactoryService
     * @param string|null $name
     */
    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        WorkerService $workerService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactoryService $resqueJobFactoryService,
        $name = null
    ) {
        parent::__construct($name);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->workerService = $workerService;
        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactoryService = $resqueJobFactoryService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:perform:all')
            ->setDescription('Perform all jobs queued')
            ->addOption('dry-run')
            ->setHelp('Perform all jobs queued');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $queuedTaskIds = $this->taskService->getEntityRepository()->getIdsByState(
            $this->taskService->getQueuedState()
        );
        $output->writeln(count($queuedTaskIds).' queued tasks ready to be performed');

        foreach ($queuedTaskIds as $taskId) {
            $output->writeln('Issuing perform command for task '.$taskId);

            $outputBuffer = new StringOutput();

            if ($this->isDryRun($input)) {
                $commandResponse = 'dry run';
            } else {
                $performCommand = new PerformCommand(
                    $this->logger,
                    $this->taskService,
                    $this->workerService,
                    $this->resqueQueueService,
                    $this->resqueJobFactoryService
                );

                $input = new ArrayInput([
                    'id' => $taskId
                ]);

                $commandResponse = $performCommand->run($input, $outputBuffer);
            }

            $output->writeln(trim($outputBuffer->getBuffer()));
            $output->writeln('Command completed with return code '.$commandResponse);
        }

        return 0;
    }

    /**
     * @param InputInterface $input
     *
     * @return bool
     */
    private function isDryRun(InputInterface $input)
    {
        return $input->getOption('dry-run') !== false;
    }
}
