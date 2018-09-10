<?php

namespace App\Tests\Unit\Command\Task;

use Psr\Log\LoggerInterface;
use App\Command\Task\PerformCommand;
use App\Services\TaskService;
use App\Services\WorkerService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Factory\MockFactory;
use App\Services\Resque\QueueService as ResqueQueueService;

/**
 * @group Command/Task/PerformCommand
 */
class PerformCommandTest extends \PHPUnit\Framework\TestCase
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
                    'with' => 'TaskPerformCommand::execute: [' . self::TASK_ID . '] does not exist',
                ],
            ]),
        ]);

        $this->assertEquals(
            PerformCommand::RETURN_CODE_TASK_DOES_NOT_EXIST,
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
     * @return PerformCommand
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

        return new PerformCommand(
            $services[LoggerInterface::class],
            $services[TaskService::class],
            $services[WorkerService::class],
            $services[ResqueQueueService::class]
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
