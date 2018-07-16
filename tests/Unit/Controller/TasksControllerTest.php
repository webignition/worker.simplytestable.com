<?php

namespace App\Tests\Unit\Controller;

use Mockery\MockInterface;
use App\Controller\TasksController;
use App\Resque\Job\TasksRequestJob;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\TasksService;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Factory\MockFactory;

/**
 * @group Controller/TasksController
 */
class TasksControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider notifyActionDataProvider
     *
     * @param ResqueQueueService $resqueQueueService
     * @param TasksService $tasksService
     * @throws \Exception
     */
    public function testNotifyAction(
        ResqueQueueService $resqueQueueService,
        TasksService $tasksService
    ) {
        $tasksController = new TasksController();

        $response = $tasksController->notifyAction($resqueQueueService, $tasksService);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @return array
     */
    public function notifyActionDataProvider()
    {
        return [
            'tasks-request queue empty' => [
                'resqueQueueService' => $this->createResqueQueueServiceWithEnqueueCall(),
                'tasksService' => MockFactory::createTasksService([
                    'getWorkerProcessCount' => [
                        'return' => 1,
                    ],
                ]),
            ],
            'tasks-request queue not empty' => [
                'resqueQueueService' => $this->createResqueQueueService(false),
                'tasksService' => MockFactory::createTasksService(),
            ],
        ];
    }

    /**
     * @return MockInterface|ResqueQueueService
     */
    private function createResqueQueueServiceWithEnqueueCall()
    {
        $resqueQueueService = $this->createResqueQueueService(true);

        $resqueQueueService
            ->shouldReceive('enqueue')
            ->withArgs(function (TasksRequestJob $tasksRequestJob) {
                $this->assertInstanceOf(TasksRequestJob::class, $tasksRequestJob);
                return true;
            });

        return $resqueQueueService;
    }

    /**
     * @param bool $isEmpty
     *
     * @return MockInterface|ResqueQueueService
     */
    private function createResqueQueueService($isEmpty)
    {
        $resqueQueueService = \Mockery::mock(ResqueQueueService::class);

        $resqueQueueService
            ->shouldReceive('isEmpty')
            ->with('tasks-request')
            ->andReturn($isEmpty);

        return $resqueQueueService;
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
