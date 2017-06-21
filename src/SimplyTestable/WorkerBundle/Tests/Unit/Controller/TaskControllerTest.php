<?php

namespace SimplyTestable\WorkerBundle\Tests\Unit\Controller\Job;

use SimplyTestable\WorkerBundle\Request\Task\CancelRequest;
use SimplyTestable\WorkerBundle\Request\Task\CreateRequest;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\ContainerFactory;
use SimplyTestable\WorkerBundle\Controller\TaskController;
use SimplyTestable\WorkerBundle\Tests\Factory\WorkerServiceFactory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TaskControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateActionInMaintenanceReadOnlyMode()
    {
        $response = $this->createTaskControllerForMaintenanceReadOnlyMode()->createAction();
        $this->assertEquals(503, $response->getStatusCode());
    }

    public function testCreateActionWithInvalidRequest()
    {
        $taskCreateRequest = \Mockery::mock(CreateRequest::class);
        $taskCreateRequest
            ->shouldReceive('isValid')
            ->andReturn(false);

        $taskCreateRequestFactory = \Mockery::mock(CreateRequestFactory::class);
        $taskCreateRequestFactory
            ->shouldReceive('create')
            ->andReturn($taskCreateRequest);

        $controller = new TaskController();
        $controller->setContainer(
            ContainerFactory::create([
                'simplytestable.services.workerservice' => WorkerServiceFactory::create(false),
                'simplytestable.services.request.factory.task.create' => $taskCreateRequestFactory,
            ])
        );

        $this->setExpectedException(
            BadRequestHttpException::class
        );

        $controller->createAction();
    }

    public function testCreateCollectionActionInMaintenanceReadOnlyMode()
    {
        $response = $this->createTaskControllerForMaintenanceReadOnlyMode()->createCollectionAction();
        $this->assertEquals(503, $response->getStatusCode());
    }

    public function testCancelActionInMaintenanceReadOnlyMode()
    {
        $response = $this->createTaskControllerForMaintenanceReadOnlyMode()->cancelAction();
        $this->assertEquals(503, $response->getStatusCode());
    }

    public function testCancelActionWithInvalidRequest()
    {
        $taskCreateRequest = \Mockery::mock(CancelRequest::class);
        $taskCreateRequest
            ->shouldReceive('isValid')
            ->andReturn(false);

        $taskCancelRequestFactory = \Mockery::mock(CancelRequestFactory::class);
        $taskCancelRequestFactory
            ->shouldReceive('create')
            ->andReturn($taskCreateRequest);

        $controller = new TaskController();
        $controller->setContainer(
            ContainerFactory::create([
                'simplytestable.services.workerservice' => WorkerServiceFactory::create(false),
                'simplytestable.services.request.factory.task.cancel' => $taskCancelRequestFactory,
            ])
        );

        $this->setExpectedException(
            BadRequestHttpException::class
        );

        $controller->cancelAction();
    }

    public function testCancelCollectionActionInMaintenanceReadOnlyMode()
    {
        $response = $this->createTaskControllerForMaintenanceReadOnlyMode()->cancelCollectionAction();
        $this->assertEquals(503, $response->getStatusCode());
    }

    /**
     * @return TaskController
     */
    private function createTaskControllerForMaintenanceReadOnlyMode()
    {
        $controller = new TaskController();
        $controller->setContainer(
            ContainerFactory::create([
                'simplytestable.services.workerservice' => WorkerServiceFactory::create(true),
            ])
        );

        return $controller;
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
