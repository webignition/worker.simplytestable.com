<?php

namespace Tests\WorkerBundle\Factory;

use Mockery\Mock;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
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
     * @return Mock|ResqueQueueService
     */
    public static function createResqueQueueService()
    {
        /* @var Mock|ResqueQueueService $resqueQueueService */
        $resqueQueueService = \Mockery::mock(ResqueQueueService::class);

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

        return $workerService;
    }
}
