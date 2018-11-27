<?php

namespace App\Tests\Unit\Command\Task;

use App\Entity\ThisWorker;
use App\Services\TaskCompletionReporter;
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
    public function testRunForInvalidTask()
    {
        $command = $this->createReportCompletionCommand([
            WorkerService::class => MockFactory::createWorkerService([
                'get' => [
                    'return' => \Mockery::mock(ThisWorker::class),
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
                    'with' => sprintf(
                        'simplytestable:task:reportcompletion::execute [%s]: [%s] does not exist',
                        self::TASK_ID,
                        self::TASK_ID
                    ),
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

        if (!isset($services[TaskCompletionReporter::class])) {
            $services[TaskCompletionReporter::class] = \Mockery::mock(TaskCompletionReporter::class);
        }

        return new ReportCompletionCommand(
            $services[LoggerInterface::class],
            $services[TaskService::class],
            $services[WorkerService::class],
            $services[TaskCompletionReporter::class]
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
