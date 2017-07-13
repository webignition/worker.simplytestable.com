<?php

namespace Tests\WorkerBundle\Unit\Services;

use Psr\Log\LoggerInterface;
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
            'taskService' => $taskService,
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
        if (!isset($services['logger'])) {
            $services['logger'] = \Mockery::mock(LoggerInterface::class);
        }

        if (!isset($services['urlService'])) {
            $services['urlService'] = \Mockery::mock(UrlService::class);
        }

        if (!isset($services['coreApplicationRouter'])) {
            $services['coreApplicationRouter'] = \Mockery::mock(CoreApplicationRouter::class);
        }

        if (!isset($services['workerService'])) {
            $services['workerService'] = \Mockery::mock(WorkerService::class);
        }

        if (!isset($services['httpClientService'])) {
            $services['httpClientService'] = \Mockery::mock(HttpClientService::class);
        }

        if (!isset($services['taskService'])) {
            $services['taskService'] = \Mockery::mock(TaskService::class);
        }

        return new TasksService(
            $services['logger'],
            $services['urlService'],
            $services['coreApplicationRouter'],
            $services['workerService'],
            $services['httpClientService'],
            $services['taskService']
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
