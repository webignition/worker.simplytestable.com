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
     * @param array $calls
     *
     * @return Mock|ResqueQueueService
     */
    public static function createResqueQueueService($calls = [])
    {
        /* @var Mock|ResqueQueueService $resqueQueueService */
        $resqueQueueService = \Mockery::mock(ResqueQueueService::class);

        if (isset($calls['enqueue'])) {
            $callValues = $calls['enqueue'];

            $with = $callValues['with'];

            $resqueQueueService
                ->shouldReceive('enqueue')
                ->with($with);
        }

        if (isset($calls['contains'])) {
            $callValues = $calls['contains'];

            $withArgs = $callValues['withArgs'];
            $return = $callValues['return'];

            $resqueQueueService
                ->shouldReceive('contains')
                ->withArgs($withArgs)
                ->andReturn($return);
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

            $withArgs = $callValues['withArgs'];
            $return = $callValues['return'];

            $resqueJobFactory
                ->shouldReceive('create')
                ->withArgs($withArgs)
                ->andReturn($return);
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

            $with = $callValues['with'];

            $logger
                ->shouldReceive('error')
                ->with($with);
        }

        return $logger;
    }

    /**
     * @return Mock|WorkerService
     */
    public static function createWorkerService()
    {
        /* @var Mock|WorkerService $workerService */
        $workerService = \Mockery::mock(WorkerService::class);

        return $workerService;
    }
}
