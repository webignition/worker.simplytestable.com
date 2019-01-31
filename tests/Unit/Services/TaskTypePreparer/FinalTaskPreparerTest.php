<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Services\TaskTypePreparer\FinalTaskPreparer;
use Doctrine\ORM\EntityManagerInterface;

class FinalTaskPreparerTest extends \PHPUnit\Framework\TestCase
{
    public function testInvokeEventPropagationStopped()
    {
        $task = new Task();
        $taskEvent = new TaskEvent($task);
        $taskEvent->stopPropagation();

        $entityManager = \Mockery::mock(EntityManagerInterface::class);
        $entityManager
            ->shouldNotReceive('persist');
        $entityManager
            ->shouldNotReceive('flush');

        $finalTaskPreparer = new FinalTaskPreparer($entityManager);
        $finalTaskPreparer->__invoke($taskEvent);

        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function testInvokeEventPropagationNotStopped()
    {
        $task = new Task();
        $taskEvent = new TaskEvent($task);

        $entityManager = \Mockery::mock(EntityManagerInterface::class);
        $entityManager
            ->shouldReceive('persist')
            ->once()
            ->with($task);

        $entityManager
            ->shouldReceive('flush')
            ->once();

        $finalTaskPreparer = new FinalTaskPreparer($entityManager);
        $finalTaskPreparer->__invoke($taskEvent);

        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
