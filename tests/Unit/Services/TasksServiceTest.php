<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Unit\Services;

use App\Services\ApplicationConfiguration;
use Psr\Log\LoggerInterface;
use App\Services\CoreApplicationHttpClient;
use App\Services\TaskService;
use App\Services\TasksService;

class TasksServiceTest extends \PHPUnit\Framework\TestCase
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

        $this->assertFalse($tasksService->request());
    }

    private function createTasksService(array $services = []): TasksService
    {
        if (!isset($services[LoggerInterface::class])) {
            $services[LoggerInterface::class] = \Mockery::mock(LoggerInterface::class);
        }

        if (!isset($services[ApplicationConfiguration::class])) {
            $services[ApplicationConfiguration::class] = \Mockery::mock(ApplicationConfiguration::class);
        }

        if (!isset($services[TaskService::class])) {
            $services[TaskService::class] = \Mockery::mock(TaskService::class);
        }

        if (!isset($services[CoreApplicationHttpClient::class])) {
            $services[CoreApplicationHttpClient::class] = \Mockery::mock(CoreApplicationHttpClient::class);
        }

        return new TasksService(
            $services[LoggerInterface::class],
            $services[ApplicationConfiguration::class],
            $services[TaskService::class],
            $services[CoreApplicationHttpClient::class],
            1,
            1
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
