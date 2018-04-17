<?php

namespace Tests\WorkerBundle\Unit\Services;

use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Services\CoreApplicationHttpClient;
use SimplyTestable\WorkerBundle\Services\CoreApplicationRouter;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use SimplyTestable\WorkerBundle\Services\UrlService;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class TasksServiceTest extends \PHPUnit_Framework_TestCase
{
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
