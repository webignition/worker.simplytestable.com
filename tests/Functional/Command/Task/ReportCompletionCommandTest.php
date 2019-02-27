<?php

namespace App\Tests\Functional\Command\Task;

use App\Command\Task\ReportCompletionCommand;
use App\Entity\Task\Task;
use App\Services\TaskCompletionReporter;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TestTaskFactory;
use Mockery\MockInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @group Command/Task/ReportCompletionCommand
 */
class ReportCompletionCommandTest extends AbstractBaseTestCase
{
    /**
     * @var ReportCompletionCommand
     */
    private $command;

    /**
     * @var Task
     */
    private $task;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(ReportCompletionCommand::class);
        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $this->task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param bool $taskCompletionReporterReturnValue
     * @param int $expectedCommandReturnValue
     *
     * @throws \Exception
     */
    public function testRun(bool $taskCompletionReporterReturnValue, int $expectedCommandReturnValue)
    {
        /* @var TaskCompletionReporter|MockInterface $taskCompletionReporter */
        $taskCompletionReporter = \Mockery::mock(TaskCompletionReporter::class);
        $taskCompletionReporter
            ->shouldReceive('reportCompletion')
            ->with($this->task)
            ->once()
            ->andReturn($taskCompletionReporterReturnValue);

        ObjectReflector::setProperty(
            $this->command,
            ReportCompletionCommand::class,
            'taskCompletionReporter',
            $taskCompletionReporter
        );

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $this->task->getId()
        ]), new NullOutput());

        $this->assertEquals(
            $expectedCommandReturnValue,
            $returnCode
        );
    }

    public function runDataProvider(): array
    {
        return [
            'success' => [
                'taskCompletionReporterReturnValue' => true,
                'expectedCommandReturnValue' => 0,
            ],
            'failure' => [
                'taskCompletionReporterReturnValue' => false,
                'expectedCommandReturnValue' => ReportCompletionCommand::RETURN_CODE_FAILED,
            ],
        ];
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
