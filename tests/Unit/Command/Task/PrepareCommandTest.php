<?php

namespace App\Tests\Unit\Command\Task;

use App\Command\Task\PrepareCommand;
use App\Services\TaskPreparer;
use Psr\Log\LoggerInterface;
use App\Services\TaskService;
use App\Services\WorkerService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Factory\MockFactory;
use App\Services\Resque\QueueService as ResqueQueueService;

/**
 * @group Command/Task/PrepareCommand
 */
class PrepareCommandTest extends \PHPUnit\Framework\TestCase
{
    const TASK_ID = 1;

    /**
     * @throws \Exception
     */
    public function testRunWithInvalidTask()
    {
        $command = $this->createPerformCommand([
            TaskService::class => MockFactory::createTaskService([
                'getById' => [
                    'with' => self::TASK_ID,
                    'return' => null,
                ],
            ]),
            LoggerInterface::class => MockFactory::createLogger([
                'error' => [
                    'with' => sprintf(
                        'simplytestable:task:prepare::execute [%s]: [%s] does not exist',
                        self::TASK_ID,
                        self::TASK_ID
                    ),
                ],
            ]),
        ]);

        $this->assertEquals(
            PrepareCommand::RETURN_CODE_TASK_DOES_NOT_EXIST,
            $command->run(
                new ArrayInput([
                    'id' => self::TASK_ID,
                ]),
                new NullOutput()
            )
        );
    }

    /**
     * @param array $services
     *
     * @return PrepareCommand
     */
    private function createPerformCommand($services = [])
    {
        if (!isset($services[LoggerInterface::class])) {
            $services[LoggerInterface::class] = MockFactory::createLogger();
        }

        if (!isset($services[TaskService::class])) {
            $services[TaskService::class] = MockFactory::createTaskService();
        }

        if (!isset($services[WorkerService::class])) {
            $services[WorkerService::class] = MockFactory::createWorkerService();
        }

        if (!isset($services[ResqueQueueService::class])) {
            $services[ResqueQueueService::class] = MockFactory::createResqueQueueService();
        }

        if (!isset($services[TaskPreparer::class])) {
            $services[TaskPreparer::class] = \Mockery::mock(TaskPreparer::class);
        }

        return new PrepareCommand(
            $services[LoggerInterface::class],
            $services[TaskService::class],
            $services[WorkerService::class],
            $services[ResqueQueueService::class],
            $services[TaskPreparer::class]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
