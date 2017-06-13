<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;

use SimplyTestable\WorkerBundle\Controller\TasksController;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

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
        $jobFactoryService = $this->container->get('simplytestable.services.resque.jobFactoryService');

        $resqueQueueService->enqueue(
            $jobFactoryService->create(
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
