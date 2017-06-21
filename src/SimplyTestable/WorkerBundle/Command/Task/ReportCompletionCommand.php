<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;

class ReportCompletionCommand extends BaseCommand
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -2;

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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:reportcompletion')
            ->setDescription('Report back to the core application the completed status of a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to report')
            ->setHelp('Report back to the core application the completed status of a task');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->workerService->isMaintenanceReadOnly()) {
            $output->writeln('Unable to report completion, worker application is in maintenance read-only mode');

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $task = $this->taskService->getById($input->getArgument('id'));
        if (is_null($task)) {
            $this->logger->error(
                "TaskReportCompletionCommand::execute: [".$input->getArgument('id')."] does not exist"
            );
            $output->writeln("[" . $input->getArgument('id')."] does not exist");

            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }

        $reportCompletionResult = $this->taskService->reportCompletion($task);

        if ($reportCompletionResult === true) {
            $output->writeln('Reported task completion ['.$task->getId().']');

            $this->entityManager->remove($task);
            $this->entityManager->remove($task->getOutput());
            $this->entityManager->remove($task->getTimePeriod());
            $this->entityManager->flush();

            return 0;
        }

        if (strlen($reportCompletionResult) === 3) {
            $output->writeln('Report completion failed, HTTP response '.$reportCompletionResult);
        } else {
            $output->writeln('Report completion failed, CURL error '.$reportCompletionResult);
        }

        return $reportCompletionResult;
    }
}
