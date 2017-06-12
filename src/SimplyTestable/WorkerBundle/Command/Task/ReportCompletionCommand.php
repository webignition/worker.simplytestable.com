<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReportCompletionCommand extends Command
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -2;

    protected function configure()
    {
        $this
            ->setName('simplytestable:task:reportcompletion')
            ->setDescription('Report back to the core application the completed status of a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to report')
            ->setHelp(<<<EOF
Report back to the core application the completed status of a task
EOF
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getWorkerService()->isMaintenanceReadOnly()) {
            $output->writeln('Unable to report completion, worker application is in maintenance read-only mode');

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $task = $this->getTaskService()->getById($input->getArgument('id'));
        if (is_null($task)) {
            $this->getContainer()->get('logger')->error(
                "TaskReportCompletionCommand::execute: [".$input->getArgument('id')."] does not exist"
            );
            $output->writeln("[" . $input->getArgument('id')."] does not exist");

            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }

        $reportCompletionResult = $this->getTaskService()->reportCompletion($task);

        if ($reportCompletionResult === true) {
            $output->writeln('Reported task completion ['.$task->getId().']');

            $entityManager = $this->getContainer()->get('doctrine')->getManager();
            $entityManager->remove($task);
            $entityManager->remove($task->getOutput());
            $entityManager->remove($task->getTimePeriod());
            $entityManager->flush();

            return 0;
        }

        if ($this->isHttpStatusCode($reportCompletionResult)) {
            $output->writeln('Report completion failed, HTTP response '.$reportCompletionResult);
        } else {
            $output->writeln('Report completion failed, CURL error '.$reportCompletionResult);
        }

        return $reportCompletionResult;
    }
}
