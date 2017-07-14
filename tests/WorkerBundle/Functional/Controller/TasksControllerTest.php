<?php

namespace Tests\WorkerBundle\Functional\Controller;

use SimplyTestable\WorkerBundle\Controller\TasksController;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class TasksControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var TasksController
     */
    private $tasksController;

    /**
     * @var QueueService
     */
    private $resqueQueueService;

    /**
     * @var JobFactory
     */
    private $resqueJobFactory;

    /**
     * @var TasksService
     */
    private $tasksService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->tasksController = new TasksController();
        $this->resqueQueueService = $this->container->get(QueueService::class);
        $this->resqueJobFactory = $this->container->get(JobFactory::class);
        $this->tasksService = $this->container->get(TasksService::class);
    }

    public function testNotifyActionWithResqueQueueEmpty()
    {
        $this->clearRedis();
        $this->assertTrue($this->resqueQueueService->isEmpty('tasks-request'));

        $response = $this->tasksController->notifyAction(
            $this->resqueQueueService,
            $this->resqueJobFactory,
            $this->tasksService
        );
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertFalse($this->resqueQueueService->isEmpty('tasks-request'));
        $this->assertEquals(1, $this->resqueQueueService->getQueueLength('tasks-request'));
    }

    public function testNotifyActionWithResqueQueueNotEmpty()
    {
        $this->clearRedis();

        $this->resqueQueueService->enqueue(
            $this->resqueJobFactory->create(
                'tasks-request',
                ['limit' => $this->container->getParameter('worker_process_count')]
            )
        );

        $this->assertFalse($this->resqueQueueService->isEmpty('tasks-request'));
        $this->assertEquals(1, $this->resqueQueueService->getQueueLength('tasks-request'));

        $response = $this->tasksController->notifyAction(
            $this->resqueQueueService,
            $this->resqueJobFactory,
            $this->tasksService
        );
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(1, $this->resqueQueueService->getQueueLength('tasks-request'));
    }
}
