<?php

namespace App\Tests\Unit\Command\Task;

use App\Entity\ThisWorker;
use Psr\Log\LoggerInterface;
use App\Command\Task\ReportCompletionCommand;
use App\Services\TaskService;
use App\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/Task/ReportCompletionCommand
 */
class ReportCompletionCommandTest extends \PHPUnit\Framework\TestCase
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

        $command = $this->createReportCompletionCommand([
            WorkerService::class => MockFactory::createWorkerService([
                'get' => [
                    'return' => $worker,
                ],
            ]),
        ]);

        $returnCode = $command->run(new ArrayInput([
            'id' => self::TASK_ID,
        ]), new NullOutput());

        $this->assertEquals(
            ReportCompletionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    /**
     * @throws \Exception
     */
    public function testRunForInvalidTask()
    {
        $worker = \Mockery::mock(ThisWorker::class);
        $worker
            ->shouldReceive('isMaintenanceReadOnly')
            ->andReturn(false);

        $command = $this->createReportCompletionCommand([
            WorkerService::class => MockFactory::createWorkerService([
                'get' => [
                    'return' => $worker,
                ],
            ]),
            TaskService::class => MockFactory::createTaskService([
                'getById' => [
                    'with' => self::TASK_ID,
                    'return' => null,
                ],
            ]),
            LoggerInterface::class => MockFactory::createLogger([
                'error' => [
                    'with' => 'TaskReportCompletionCommand::execute: [' . self::TASK_ID . '] does not exist',
                ],
            ]),
        ]);

        $returnCode = $command->run(new ArrayInput([
            'id' => self::TASK_ID,
        ]), new NullOutput());

        $this->assertEquals(
            ReportCompletionCommand::RETURN_CODE_TASK_DOES_NOT_EXIST,
            $returnCode
        );
    }

    /**
     * @param array $services
     *
     * @return ReportCompletionCommand
     */
    private function createReportCompletionCommand($services = [])
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

        return new ReportCompletionCommand(
            $services[LoggerInterface::class],
            $services[TaskService::class],
            $services[WorkerService::class]
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
