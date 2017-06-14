<?php
namespace SimplyTestable\WorkerBundle\Command\Task;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PerformCommand extends Command
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -2;
    const RETURN_CODE_FAILED_DUE_TO_WRONG_STATE = -3;
    const RETURN_CODE_UNKNOWN_ERROR = -5;
    const RETURN_CODE_TASK_SERVICE_RAISED_EXCEPTION = -6;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:perform')
            ->setDescription('Start a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to start')
            ->setHelp('Start a task'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');
        $task = $this->getTaskService()->getById($input->getArgument('id'));

        if (is_null($task)) {
            $logger->error("TaskPerformCommand::execute: [".$input->getArgument('id')."] does not exist");
            $output->writeln('Unable to execute, task '.$input->getArgument('id').' does not exist');

            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }

        $logger->info("TaskPerformCommand::execute: [".$task->getId()."] [".$task->getState()->getName()."]");

        if ($this->getWorkerService()->isMaintenanceReadOnly()) {
            if (!$this->getResqueQueueService()->contains('task-perform', ['id' => $task->getId()])) {
                $this->getResqueQueueService()->enqueue(
                    $this->getResqueJobFactoryService()->create('task-perform', ['id' => $task->getId()])
                );
            }

            $output->writeln('Unable to perform task, worker application is in maintenance read-only mode');

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        try {
            $performResult = $this->getTaskService()->perform($task);
        } catch (\Exception $e) {
            $logger->error('TaskPerformCommand: Exception: taskId: [' . $task->getId() . ']');
            $logger->error('TaskPerformCommand: Exception: exception class: [' . get_class($e) . ']');
            $logger->error('TaskPerformCommand: Exception: exception code: [' . $e->getCode() . ']');
            $logger->error('TaskPerformCommand: Exception: exception msg: [' . $e->getMessage() . ']');

            return self::RETURN_CODE_TASK_SERVICE_RAISED_EXCEPTION;
        }

        if ($this->getResqueQueueService()->isEmpty('tasks-request')) {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'tasks-request'
                )
            );
        }

        if ($performResult === 0) {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'task-report-completion',
                    ['id' => $task->getId()]
                )
            );

            $output->writeln('Performed ['.$task->getId().']');
            $logger->info(sprintf(
                'TaskPerformCommand::Performed [%d] [%s] [%s]',
                $task->getId(),
                $task->getState(),
                ($task->hasOutput() ? 'has output' : 'no output')
            ));

            return 0;
        }

        if ($performResult === 1) {
            $output->writeln('Task perform failed, task is in wrong state (currently:'.$task->getState().')');

            return self::RETURN_CODE_FAILED_DUE_TO_WRONG_STATE;
        }

        $output->writeln('Task perform failed, unknown error');

        return self::RETURN_CODE_UNKNOWN_ERROR;
    }
}
