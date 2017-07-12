<?php

namespace Tests\WorkerBundle\Functional\Controller;

use SimplyTestable\WorkerBundle\Controller\TasksController;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class TasksControllerTest extends BaseSimplyTestableTestCase
{
    public function testNotifyActionWithResqueQueueEmpty()
    {
        $this->clearRedis();
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $this->assertTrue($resqueQueueService->isEmpty('tasks-request'));

        $response = $this->createTasksController()->notifyAction();
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertFalse($resqueQueueService->isEmpty('tasks-request'));
        $this->assertEquals(1, $resqueQueueService->getQueueLength('tasks-request'));
    }

    public function testNotifyActionWithResqueQueueNotEmpty()
    {
        $this->clearRedis();

        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $resqueQueueService->enqueue(
            $resqueJobFactory->create(
                'tasks-request',
                ['limit' => $this->container->getParameter('worker_process_count')]
            )
        );

        $this->assertFalse($resqueQueueService->isEmpty('tasks-request'));
        $this->assertEquals(1, $resqueQueueService->getQueueLength('tasks-request'));

        $response = $this->createTasksController()->notifyAction();
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(1, $resqueQueueService->getQueueLength('tasks-request'));
    }

    /**
     * @return TasksController
     */
    private function createTasksController()
    {
        $controller = new TasksController();
        $controller->setContainer($this->container);

        return $controller;
    }
}
