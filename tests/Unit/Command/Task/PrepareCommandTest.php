<?php

namespace App\Tests\Unit\Command\Task;

use App\Command\Task\PrepareCommand;
use App\Services\TaskPreparer;
use Psr\Log\LoggerInterface;
use App\Services\TaskService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Factory\MockFactory;

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

    private function createPerformCommand(array $services = []): PrepareCommand
    {
        if (!isset($services[LoggerInterface::class])) {
            $services[LoggerInterface::class] = MockFactory::createLogger();
        }

        if (!isset($services[TaskService::class])) {
            $services[TaskService::class] = MockFactory::createTaskService();
        }

        if (!isset($services[TaskPreparer::class])) {
            $services[TaskPreparer::class] = \Mockery::mock(TaskPreparer::class);
        }

        return new PrepareCommand(
            $services[LoggerInterface::class],
            $services[TaskService::class],
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
