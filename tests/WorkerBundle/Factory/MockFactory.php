<?php

namespace Tests\WorkerBundle\Factory;

use Mockery\Mock;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\VerifyRequestFactory;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\TaskFactory;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class MockFactory
{
    /**
     * @param array $calls
     *
     * @return Mock|TaskService
     */
    public static function createTaskService($calls = [])
    {
        /* @var Mock|TaskService $taskService */
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
     * @return Mock|ResqueQueueService
     */
    public static function createResqueQueueService($calls = [])
    {
        /* @var Mock|ResqueQueueService $resqueQueueService */
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

        return $resqueQueueService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|ResqueJobFactory
     */
    public static function createResqueJobFactory($calls = [])
    {
        /* @var Mock|ResqueJobFactory $resqueJobFactory */
        $resqueJobFactory = \Mockery::mock(ResqueJobFactory::class);

        if (isset($calls['create'])) {
            $callValues = $calls['create'];

            $resqueJobFactory
                ->shouldReceive('create')
                ->withArgs($callValues['withArgs'])
                ->andReturn($callValues['return']);
        }

        return $resqueJobFactory;
    }

    /**
     * @param array $calls
     *
     * @return Mock|LoggerInterface
     */
    public static function createLogger($calls = [])
    {
        /* @var Mock|LoggerInterface $logger */
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
     * @return Mock|WorkerService
     */
    public static function createWorkerService($calls = [])
    {
        /* @var Mock|WorkerService $workerService */
        $workerService = \Mockery::mock(WorkerService::class);

        if (isset($calls['isMaintenanceReadOnly'])) {
            $callValues = $calls['isMaintenanceReadOnly'];

            $workerService
                ->shouldReceive('isMaintenanceReadOnly')
                ->andReturn($callValues['return']);
        }

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
     * @return Mock|TasksService
     */
    public static function createTasksService($calls = [])
    {
        /* @var Mock|TasksService $tasksService */
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
     * @return Mock|CreateRequestCollectionFactory
     */
    public static function createCreateRequestCollectionFactory()
    {
        /* @var Mock|CreateRequestCollectionFactory $createRequestCollectionFactory */
        $createRequestCollectionFactory = \Mockery::mock(CreateRequestCollectionFactory::class);

        return $createRequestCollectionFactory;
    }

    /**
     * @return Mock|TaskFactory
     */
    public static function createTaskFactory()
    {
        /* @var Mock|TaskFactory $taskFactory */
        $taskFactory = \Mockery::mock(TaskFactory::class);

        return $taskFactory;
    }

    /**
     * @param array $calls
     *
     * @return Mock|CancelRequestFactory
     */
    public static function createCancelRequestFactory($calls = [])
    {
        /* @var Mock|CancelRequestFactory $cancelRequestFactory */
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
     * @return Mock|CancelRequestCollectionFactory
     */
    public static function createCancelRequestCollectionFactory()
    {
        /* @var Mock|CancelRequestCollectionFactory $cancelRequestCollectionFactory */
        $cancelRequestCollectionFactory = \Mockery::mock(CancelRequestCollectionFactory::class);

        return $cancelRequestCollectionFactory;
    }

    /**
     * @param array $calls
     *
     * @return Mock|VerifyRequestFactory
     */
    public static function createVerifyRequestFactory($calls = [])
    {
        /* @var Mock|VerifyRequestFactory $verifyRequestFactory */
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
