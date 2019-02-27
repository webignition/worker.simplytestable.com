<?php

namespace App\Tests\Functional\Command\Task;

use App\Entity\Task\Task;
use App\Resque\Job\TasksRequestJob;
use App\Services\TaskPerformer;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TestTaskFactory;
use App\Command\Task\PerformCommand;
use App\Services\Resque\QueueService;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/Task/PerformCommand
 */
class PerformCommandTest extends AbstractBaseTestCase
{
    /**
     * @var PerformCommand
     */
    private $command;

    /**
     * @var Task
     */
    private $task;

    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(PerformCommand::class);
        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $this->task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));
    }

    /**
     * @throws \Exception
     */
    public function testRunSuccess()
    {
        $resqueQueueService = self::$container->get(QueueService::class);

        $taskPerformer = \Mockery::mock(TaskPerformer::class);
        $taskPerformer
            ->shouldReceive('perform')
            ->once()
            ->with($this->task);

        ObjectReflector::setProperty($this->command, PerformCommand::class, 'taskPerformer', $taskPerformer);

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $this->task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertFalse($resqueQueueService->isEmpty(TasksRequestJob::QUEUE_NAME));
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
