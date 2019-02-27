<?php

namespace App\Tests\Functional\Command\Task;

use App\Command\Task\PrepareCommand;
use App\Entity\Task\Task;
use App\Services\TaskPreparer;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TestTaskFactory;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/Task/PrepareCommand
 */
class PrepareCommandTest extends AbstractBaseTestCase
{
    /**
     * @var PrepareCommand
     */
    private $command;

    /**
     * @var Task
     */
    private $task;

    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(PrepareCommand::class);
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
        $taskPreparer = \Mockery::mock(TaskPreparer::class);
        $taskPreparer
            ->shouldReceive('prepare')
            ->once()
            ->with($this->task);

        ObjectReflector::setProperty($this->command, PrepareCommand::class, 'taskPreparer', $taskPreparer);

        $this->assertEquals(Task::STATE_QUEUED, $this->task->getState());

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $this->task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
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
