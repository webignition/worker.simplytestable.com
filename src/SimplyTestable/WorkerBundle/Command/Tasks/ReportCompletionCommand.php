<?php
namespace SimplyTestable\WorkerBundle\Command\Tasks;

use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionCommand as TaskReportCompletionCommand;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReportCompletionCommand extends AbstractTaskCollectionCommand
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
     * @param LoggerInterface $logger
     * @param TaskService $taskService
     * @param WorkerService $workerService
     * @param string|null $name
     */
    public function __construct(
        LoggerInterface $logger,
        TaskService $taskService,
        WorkerService $workerService,
        $name = null
    ) {
        parent::__construct($name);

        $this->logger = $logger;
        $this->taskService = $taskService;
        $this->workerService = $workerService;
    }

    protected function configure()
    {
        $this
            ->setName('simplytestable:tasks:reportcompletion')
            ->setDescription('Report completion for all jobs finished')
            ->setHelp('Report completion for all jobs finished');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $taskIds = $this->taskService->getEntityRepository()->getIdsWithOutput();
        $output->writeln(count($taskIds).' tasks with output ready to report completion');

        $reportCompletionCommand = new TaskReportCompletionCommand(
            $this->logger,
            $this->taskService,
            $this->workerService
        );

        $this->executeForCollection($taskIds, $reportCompletionCommand, $output);

        return 0;
    }
}