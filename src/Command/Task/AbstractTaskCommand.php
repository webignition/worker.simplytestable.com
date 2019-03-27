<?php

namespace App\Command\Task;

use App\Entity\Task\Task;
use App\Services\TaskService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\SymfonyConsole\TypedInput\TypedInput;

abstract class AbstractTaskCommand extends Command
{
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -2;
    const RETURN_CODE_OK = 0;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TaskService
     */
    protected $taskService;

    /**
     * @var Task
     */
    protected $task;

    public function __construct(LoggerInterface $logger, TaskService $taskService)
    {
        parent::__construct(null);

        $this->logger = $logger;
        $this->taskService = $taskService;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $typedInput = new TypedInput($input);
        $taskId = $typedInput->getIntegerArgument('id');

        $this->task = $this->taskService->getById($taskId);
        if (empty($this->task)) {
            $taskIdDoesNotExistMessage = sprintf('[%s] does not exist', $taskId);

            $this->logger->error(sprintf(
                '%s::execute [%s]: %s',
                $this->getName(),
                $taskId,
                $taskIdDoesNotExistMessage
            ));
            $output->writeln($taskIdDoesNotExistMessage);

            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }

        return self::RETURN_CODE_OK;
    }
}
