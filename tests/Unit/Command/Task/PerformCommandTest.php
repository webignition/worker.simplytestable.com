<?php

namespace App\Tests\Unit\Command\Task;

use App\Entity\Task\Task;
use App\Entity\ThisWorker;
use App\Services\TaskPerformer;
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
    public function testRunInMaintenanceReadOnlyMode()
    {
        $worker = \Mockery::mock(ThisWorker::class);
        $worker
            ->shouldReceive('isMaintenanceReadOnly')
            ->andReturn(true);

        $task = \Mockery::mock(Task::class);
        $task
            ->shouldReceive('getId')
            ->andReturn(self::TASK_ID);

        $command = $this->createPerformCommand([
            TaskService::class => MockFactory::createTaskService([
                'getById' => [
                    'with' => self::TASK_ID,
                    'return' => $task,
                ],
            ]),
            WorkerService::class => MockFactory::createWorkerService([
                'get' => [
                    'return' => $worker,
                ],
            ]),
            LoggerInterface::class => MockFactory::createLogger([
                'error' => [
                    'with' => sprintf(
                        'simplytestable:task:perform::execute [%s]: '
                        .'worker application is in maintenance read-only mode',
                        self::TASK_ID
                    ),
                ],
            ]),
            ResqueQueueService::class => MockFactory::createResqueQueueService([
                'contains' => [
                    'withArgs' => ['task-perform', ['id' => self::TASK_ID]],
                    'return' => true,
                ],
            ]),
        ]);

        $returnCode = $command->run(new ArrayInput([
            'id' => self::TASK_ID,
        ]), new NullOutput());

        $this->assertEquals(
            PerformCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

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
                        'simplytestable:task:perform::execute [%s]: [%s] does not exist',
                        self::TASK_ID,
                        self::TASK_ID
                    ),
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

        if (!isset($services[TaskPerformer::class])) {
            $services[TaskPerformer::class] = \Mockery::mock(TaskPerformer::class);
        }

        return new PerformCommand(
            $services[LoggerInterface::class],
            $services[TaskService::class],
            $services[WorkerService::class],
            $services[ResqueQueueService::class],
            $services[TaskPerformer::class]
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
