<?php

namespace SimplyTestable\WorkerBundle\Tests\Unit\Controller;

use SimplyTestable\WorkerBundle\Request\Task\CancelRequest;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\ContainerFactory;
use SimplyTestable\WorkerBundle\Controller\TaskController;
use SimplyTestable\WorkerBundle\Tests\Factory\WorkerServiceFactory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TaskControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateCollectionActionInMaintenanceReadOnlyMode()
    {
        $this->expectException(ServiceUnavailableHttpException::class);

        $this->createTaskControllerForMaintenanceReadOnlyMode()->createCollectionAction();
    }

    public function testCancelActionInMaintenanceReadOnlyMode()
    {
        $this->expectException(ServiceUnavailableHttpException::class);

        $this->createTaskControllerForMaintenanceReadOnlyMode()->cancelAction();
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

        $this->expectException(BadRequestHttpException::class);

        $controller->cancelAction();
    }

    public function testCancelCollectionActionInMaintenanceReadOnlyMode()
    {
        $this->expectException(ServiceUnavailableHttpException::class);

        $this->createTaskControllerForMaintenanceReadOnlyMode()->cancelCollectionAction();
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
