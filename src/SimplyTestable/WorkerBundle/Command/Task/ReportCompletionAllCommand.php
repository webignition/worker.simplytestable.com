<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;

class ReportCompletionAllCommand extends BaseCommand
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
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param LoggerInterface $logger
     * @param TaskService $taskService
     * @param WorkerService $workerService
     * @param EntityManager $entityManager
     * @param string|null $name
     */
    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        WorkerService $workerService,
        EntityManager $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->workerService = $workerService;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('simplytestable:task:reportcompletion:all')
            ->setDescription('Report completion for all jobs finished')
            ->addOption('dry-run')
            ->setHelp('Report completion for all jobs finished');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $taskIdsWithOutput = $this->taskService->getEntityRepository()->getIdsWithOutput();
        $output->writeln(count($taskIdsWithOutput).' tasks with output ready to report completion');

        foreach ($taskIdsWithOutput as $taskId) {
            $output->writeln('Issuing report completion command for task '.$taskId);

            $outputBuffer = new StringOutput();

            if ($this->isDryRun($input)) {
                $commandResponse = 'dry run';
            } else {
                $reportCompletionCommand = new ReportCompletionCommand(
                    $this->logger,
                    $this->taskService,
                    $this->workerService,
                    $this->entityManager
                );

                $input = new ArrayInput([
                    'id' => $taskId
                ]);

                $commandResponse = $reportCompletionCommand->run($input, $outputBuffer);
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
