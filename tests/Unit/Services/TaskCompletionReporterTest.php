<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Unit\Services;

use App\Services\CoreApplicationHttpClient;
use App\Services\TaskCompletionReporter;
use App\Entity\Task\Task;
use Mockery\MockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaskCompletionReporterTest extends \PHPUnit\Framework\TestCase
{
    public function testReportCompletionNoOutput()
    {
        /* @var CoreApplicationHttpClient|MockInterface $coreApplicationHttpClient */
        $coreApplicationHttpClient = \Mockery::mock(CoreApplicationHttpClient::class);

        /* @var EventDispatcherInterface|MockInterface $eventDispatcher */
        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);

        $taskCompletionReporter = new TaskCompletionReporter(
            $coreApplicationHttpClient,
            $eventDispatcher
        );

        $task = new Task();
        $task->setState(Task::STATE_QUEUED);

        $coreApplicationHttpClient
            ->shouldNotReceive('createPostRequest');

        $coreApplicationHttpClient
            ->shouldNotReceive('send');

        $eventDispatcher
            ->shouldNotReceive('dispatch');

        $taskCompletionReporter->reportCompletion($task);

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
