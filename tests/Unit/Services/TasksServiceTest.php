<?php

namespace App\Tests\Unit\Services;

use App\Exception\Services\TasksService\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use App\Services\CoreApplicationHttpClient;
use App\Services\TaskService;
use App\Services\TasksService;
use App\Services\WorkerService;

class TasksServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws RequestException
     * @throws GuzzleException
     */
    public function testRequestNotWithinThreshold()
    {
        $workerProcessCount = 1;

        $taskService = \Mockery::mock(TaskService::class);
        $taskService
            ->shouldReceive('getInCompleteCount')
            ->andReturn($workerProcessCount + 1);

        $tasksService = $this->createTasksService([
            TaskService::class => $taskService,
        ]);

        $tasksService->setWorkerProcessCount($workerProcessCount);

        $this->assertFalse($tasksService->request());
    }

    /**
     * @param array $services
     *
     * @return TasksService
     */
    private function createTasksService($services = [])
    {
        if (!isset($services[LoggerInterface::class])) {
            $services[LoggerInterface::class] = \Mockery::mock(LoggerInterface::class);
        }

        if (!isset($services[WorkerService::class])) {
            $services[WorkerService::class] = \Mockery::mock(WorkerService::class);
        }

        if (!isset($services[TaskService::class])) {
            $services[TaskService::class] = \Mockery::mock(TaskService::class);
        }

        if (!isset($services[CoreApplicationHttpClient::class])) {
            $services[CoreApplicationHttpClient::class] = \Mockery::mock(CoreApplicationHttpClient::class);
        }

        return new TasksService(
            $services[LoggerInterface::class],
            $services[WorkerService::class],
            $services[TaskService::class],
            $services[CoreApplicationHttpClient::class]
        );
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
