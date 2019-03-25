<?php

namespace App\Tests\Factory;

use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use App\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use App\Services\Request\Factory\Task\CancelRequestFactory;
use App\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use App\Services\Request\Factory\VerifyRequestFactory;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\TaskFactory;
use App\Services\TaskService;
use App\Services\TasksService;
use App\Services\WorkerService;

class MockFactory
{
    /**
     * @param array $calls
     *
     * @return MockInterface|TaskService
     */
    public static function createTaskService($calls = [])
    {
        /* @var MockInterface|TaskService $taskService */
        $taskService = \Mockery::mock(TaskService::class);

        if (isset($calls['getById'])) {
            $callValues = $calls['getById'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $taskService
                ->shouldReceive('getById')
                ->with($with)
                ->andReturn($return);
        }

        return $taskService;
    }

    /**
     * @param array $calls
     *
     * @return MockInterface|ResqueQueueService
     */
    public static function createResqueQueueService($calls = [])
    {
        /* @var MockInterface|ResqueQueueService $resqueQueueService */
        $resqueQueueService = \Mockery::mock(ResqueQueueService::class);

        if (isset($calls['isEmpty'])) {
            $callValues = $calls['isEmpty'];

            $resqueQueueService
                ->shouldReceive('isEmpty')
                ->with($callValues['with'])
                ->andReturn($callValues['return']);
        }

        if (isset($calls['enqueue'])) {
            $callValues = $calls['enqueue'];

            $resqueQueueService
                ->shouldReceive('enqueue')
                ->with($callValues['with']);
        }

        if (isset($calls['contains'])) {
            $callValues = $calls['contains'];

            $resqueQueueService
                ->shouldReceive('contains')
                ->withArgs($callValues['withArgs'])
                ->andReturn($callValues['return']);
        }

        return $resqueQueueService;
    }

    /**
     * @param array $calls
     *
     * @return MockInterface|LoggerInterface
     */
    public static function createLogger($calls = [])
    {
        /* @var MockInterface|LoggerInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);

        if (isset($calls['error'])) {
            $callValues = $calls['error'];

            $logger
                ->shouldReceive('error')
                ->with($callValues['with']);
        }

        return $logger;
    }

    /**
     * @param array $calls
     *
     * @return MockInterface|WorkerService
     */
    public static function createWorkerService($calls = [])
    {
        /* @var MockInterface|WorkerService $workerService */
        $workerService = \Mockery::mock(WorkerService::class);

        if (isset($calls['activate'])) {
            $callValues = $calls['activate'];

            $workerService
                ->shouldReceive('activate')
                ->andReturn($callValues['return']);
        }

        if (isset($calls['get'])) {
            $callValues = $calls['get'];

            $workerService
                ->shouldReceive('get')
                ->andReturn($callValues['return']);
        }

        return $workerService;
    }

    /**
     * @param array $calls
     *
     * @return MockInterface|TasksService
     */
    public static function createTasksService($calls = [])
    {
        /* @var MockInterface|TasksService $tasksService */
        $tasksService = \Mockery::mock(TasksService::class);

        if (isset($calls['getWorkerProcessCount'])) {
            $callValues = $calls['getWorkerProcessCount'];

            $tasksService
                ->shouldReceive('getWorkerProcessCount')
                ->andReturn($callValues['return']);
        }

        return $tasksService;
    }

    /**
     * @return MockInterface|CreateRequestCollectionFactory
     */
    public static function createCreateRequestCollectionFactory()
    {
        /* @var MockInterface|CreateRequestCollectionFactory $createRequestCollectionFactory */
        $createRequestCollectionFactory = \Mockery::mock(CreateRequestCollectionFactory::class);

        return $createRequestCollectionFactory;
    }

    /**
     * @return MockInterface|TaskFactory
     */
    public static function createTaskFactory()
    {
        /* @var MockInterface|TaskFactory $taskFactory */
        $taskFactory = \Mockery::mock(TaskFactory::class);

        return $taskFactory;
    }

    /**
     * @param array $calls
     *
     * @return MockInterface|CancelRequestFactory
     */
    public static function createCancelRequestFactory($calls = [])
    {
        /* @var MockInterface|CancelRequestFactory $cancelRequestFactory */
        $cancelRequestFactory = \Mockery::mock(CancelRequestFactory::class);

        if (isset($calls['create'])) {
            $callValues = $calls['create'];

            $cancelRequestFactory
                ->shouldReceive('create')
                ->andReturn($callValues['return']);
        }

        return $cancelRequestFactory;
    }

    /**
     * @return MockInterface|CancelRequestCollectionFactory
     */
    public static function createCancelRequestCollectionFactory()
    {
        /* @var MockInterface|CancelRequestCollectionFactory $cancelRequestCollectionFactory */
        $cancelRequestCollectionFactory = \Mockery::mock(CancelRequestCollectionFactory::class);

        return $cancelRequestCollectionFactory;
    }

    /**
     * @param array $calls
     *
     * @return MockInterface|VerifyRequestFactory
     */
    public static function createVerifyRequestFactory($calls = [])
    {
        /* @var MockInterface|VerifyRequestFactory $verifyRequestFactory */
        $verifyRequestFactory = \Mockery::mock(VerifyRequestFactory::class);

        if (isset($calls['create'])) {
            $callValues = $calls['create'];

            $verifyRequestFactory
                ->shouldReceive('create')
                ->andReturn($callValues['return']);
        }

        return $verifyRequestFactory;
    }
}
