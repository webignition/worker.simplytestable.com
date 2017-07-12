<?php

namespace SimplyTestable\WorkerBundle\Tests\Unit\Controller;

use SimplyTestable\WorkerBundle\Controller\VerifyController;
use SimplyTestable\WorkerBundle\Request\VerifyRequest;
use SimplyTestable\WorkerBundle\Services\Request\Factory\VerifyRequestFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\ContainerFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\WorkerServiceFactory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class VerifyControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testVerifyActionInMaintenanceReadOnlyMode()
    {
        $controller = new VerifyController();
        $controller->setContainer(
            ContainerFactory::create([
                'simplytestable.services.workerservice' => WorkerServiceFactory::create(true),
            ])
        );

        $this->expectException(ServiceUnavailableHttpException::class);

        $controller->indexAction();
    }

    public function testVerifyActionWithInvalidRequest()
    {
        $verifyRequest = \Mockery::mock(VerifyRequest::class);
        $verifyRequest
            ->shouldReceive('isValid')
            ->andReturn(false);

        $taskCreateRequestFactory = \Mockery::mock(VerifyRequestFactory::class);
        $taskCreateRequestFactory
            ->shouldReceive('create')
            ->andReturn($verifyRequest);

        $controller = new VerifyController();
        $controller->setContainer(
            ContainerFactory::create([
                'simplytestable.services.workerservice' => WorkerServiceFactory::create(false),
                'simplytestable.services.request.factory.verify' => $taskCreateRequestFactory,
            ])
        );

        $this->expectException(BadRequestHttpException::class);

        $controller->indexAction();
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
