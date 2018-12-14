<?php

namespace App\Tests\Unit\Services;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Task\Type;
use App\Model\TaskPreparerCollection;
use App\Services\TaskPreparer;
use App\Services\TaskTypePreparer\Factory;
use App\Services\TaskTypePreparer\TaskPreparerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaskPreparerTest extends \PHPUnit\Framework\TestCase
{
    public function testPrepareHasTaskPreparer()
    {
        $task = Task::create(
            new Type(Type::TYPE_HTML_VALIDATION, true, null),
            'http://example.com',
            Task::STATE_QUEUED
        );

        $entityManager = $this->createEntityManager([
            Task::STATE_PREPARING,
        ]);

        /* @var EventDispatcherInterface|MockInterface $eventDispatcher */
        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);

        /* @var TaskPreparerInterface|MockInterface $taskTypePreparer */
        $taskTypePreparer = \Mockery::mock(TaskPreparerInterface::class);

        $taskTypePreparer
            ->shouldReceive('prepare')
            ->once()
            ->with($task);

        $taskTypePreparer
            ->shouldReceive('getPriority')
            ->once()
            ->andReturn(1);

        $taskPreparerCollection = new TaskPreparerCollection([
            $taskTypePreparer,
        ]);

        $taskTypePreparerFactory = $this->createTaskTypePreparerFactory($task, $taskPreparerCollection);

        $taskPreparer = new TaskPreparer($entityManager, $taskTypePreparerFactory, $eventDispatcher);

        $taskPreparer->prepare($task);

        $this->assertTrue(true);
    }

    public function testStateProgression()
    {
        $task = Task::create(
            new Type(Type::TYPE_HTML_VALIDATION, true, null),
            'http://example.com',
            Task::STATE_QUEUED
        );

        $entityManager = $this->createEntityManager([
            Task::STATE_PREPARING,
            Task::STATE_PREPARED,
        ]);

        /* @var EventDispatcherInterface|MockInterface $eventDispatcher */
        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);

        $taskTypePreparerFactory = $this->createTaskTypePreparerFactory($task, new TaskPreparerCollection([]));

        $eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->withArgs(function (string $eventName, TaskEvent $taskEvent) use ($task) {
                $this->assertSame(TaskEvent::TYPE_PREPARED, $eventName);
                $this->assertSame($task, $taskEvent->getTask());

                return true;
            });

        $taskPreparer = new TaskPreparer($entityManager, $taskTypePreparerFactory, $eventDispatcher);
        $taskPreparer->prepare($task);

        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());
    }

    private function createEntityManager(array $persistCallExpectedStates): EntityManagerInterface
    {
        /* @var EntityManagerInterface|MockInterface $entityManager */
        $entityManager = \Mockery::mock(EntityManagerInterface::class);

        $expectedCallCount = count($persistCallExpectedStates);
        $persistCallCount = 0;

        $entityManager
            ->shouldReceive('persist')
            ->withArgs(function (Task $task) use (&$persistCallCount, $persistCallExpectedStates) {
                $this->assertEquals($persistCallExpectedStates[$persistCallCount], $task->getState());
                $persistCallCount++;

                return true;
            })
            ->times($expectedCallCount);

        $entityManager
            ->shouldReceive('flush')
            ->times($expectedCallCount);

        return $entityManager;
    }

    private function createTaskTypePreparerFactory(Task $task, $getPreparerReturnValue): Factory
    {
        /* @var Factory|MockInterface $taskTypePreparerFactory */
        $taskTypePreparerFactory = \Mockery::mock(Factory::class);

        $taskTypePreparerFactory
            ->shouldReceive('getPreparers')
            ->once()
            ->with($task->getType()->getName())
            ->andReturn($getPreparerReturnValue);

        return $taskTypePreparerFactory;
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
