<?php

namespace App\Tests\Unit\Services;

use App\Entity\Task\Task;
use App\Model\Task\Type;
use App\Services\TaskPreparer;
use App\Services\TaskTypePreparer\Factory;
use Doctrine\ORM\EntityManagerInterface;

class TaskPreparerTest extends \PHPUnit\Framework\TestCase
{
    public function testStateProgression()
    {
        $task = new Task();
        $task->setType(new Type(Type::TYPE_HTML_VALIDATION, true, null));

        $entityManager = \Mockery::mock(EntityManagerInterface::class);
        $taskTypePreparerFactory = \Mockery::mock(Factory::class);

        $persistCallCount = 0;
        $persistCallExpectedStates = [
            Task::STATE_PREPARING,
            Task::STATE_PREPARED,
        ];

        $entityManager
            ->shouldReceive('persist')
            ->withArgs(function (Task $task) use (&$persistCallCount, $persistCallExpectedStates) {
                $this->assertEquals($persistCallExpectedStates[$persistCallCount], $task->getState());
                $persistCallCount++;

                return true;
            })
            ->twice();

        $entityManager
            ->shouldReceive('flush')
            ->twice();

        $taskTypePreparerFactory
            ->shouldReceive('getPreparer')
            ->with($task->getType()->getName())
            ->andReturnNull();

        $taskPreparer = new TaskPreparer($entityManager, $taskTypePreparerFactory);
        $taskPreparer->prepare($task);

        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());
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
