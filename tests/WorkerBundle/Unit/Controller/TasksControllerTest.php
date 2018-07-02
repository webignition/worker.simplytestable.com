<?php

namespace Tests\WorkerBundle\Unit\Controller;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Controller\TasksController;
use SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use Tests\WorkerBundle\Factory\MockFactory;

/**
 * @group Controller/TasksController
 */
class TasksControllerTest extends \PHPUnit_Framework_TestCase
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

        $tasksController->notifyAction($resqueQueueService, $tasksService);
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
