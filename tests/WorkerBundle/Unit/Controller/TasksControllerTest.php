<?php

namespace Tests\WorkerBundle\Unit\Controller;

use SimplyTestable\WorkerBundle\Controller\TasksController;
use SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
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
     * @param ResqueJobFactory $resqueJobFactory
     * @param TasksService $tasksService
     */
    public function testNotifyAction(
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        TasksService $tasksService
    ) {
        $tasksController = new TasksController();

        $tasksController->notifyAction($resqueQueueService, $resqueJobFactory, $tasksService);
    }

    /**
     * @return array
     */
    public function notifyActionDataProvider()
    {
        $tasksRequestJob = new TasksRequestJob();

        return [
            'tasks-request queue empty' => [
                'resqueQueueService' => MockFactory::createResqueQueueService([
                    'isEmpty' => [
                        'with' => 'tasks-request',
                        'return' => true,
                    ],
                    'enqueue' => [
                        'with' => $tasksRequestJob,
                    ],
                ]),
                'resqueJobFactory' => MockFactory::createResqueJobFactory([
                    'create' => [
                        'withArgs' => [
                            'tasks-request',
                            [
                                'limit' => 1,
                            ],
                        ],
                        'return' => $tasksRequestJob,
                    ],
                ]),
                'tasksService' => MockFactory::createTasksService([
                    'getWorkerProcessCount' => [
                        'return' => 1,
                    ],
                ]),
            ],
            'tasks-request queue not empty' => [
                'resqueQueueService' => MockFactory::createResqueQueueService([
                    'isEmpty' => [
                        'with' => 'tasks-request',
                        'return' => false,
                    ],
                ]),
                'resqueJobFactory' => MockFactory::createResqueJobFactory(),
                'tasksService' => MockFactory::createTasksService(),
            ],
        ];
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
