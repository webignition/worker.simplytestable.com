<?php

namespace App\Tests\Unit\Controller;

use App\Controller\VerifyController;
use App\Entity\ThisWorker;
use App\Request\VerifyRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Tests\Factory\MockFactory;

/**
 * @group Controller/VerifyController
 */
class VerifyControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testIndexActionWithInvalidRequest()
    {
        $verifyRequest = new VerifyRequest(null, null);

        $worker = \Mockery::mock(ThisWorker::class);
        $worker
            ->shouldReceive('isMaintenanceReadOnly')
            ->andReturn(false);

        $this->expectException(BadRequestHttpException::class);

        $verifyController = new VerifyController();
        $verifyController->indexAction(
            MockFactory::createWorkerService([
                'get' => [
                    'return' => $worker,
                ],
            ]),
            MockFactory::createVerifyRequestFactory([
                'create' => [
                    'return' => $verifyRequest,
                ],
            ])
        );
    }

    public function testIndexActionWithBadRequest()
    {
        $verifyRequest = new VerifyRequest('foo', 'invalid-token');
        $worker = \Mockery::mock(ThisWorker::class);
        $worker
            ->shouldReceive('isMaintenanceReadOnly')
            ->andReturn(false);
        $worker
            ->shouldReceive('getHostname')
            ->andReturn('foo');
        $worker
            ->shouldReceive('getActivationToken')
            ->andReturn('token');

        $this->expectException(BadRequestHttpException::class);

        $verifyController = new VerifyController();
        $verifyController->indexAction(
            MockFactory::createWorkerService([
                'get' => [
                    'return' => $worker,
                ],
            ]),
            MockFactory::createVerifyRequestFactory([
                'create' => [
                    'return' => $verifyRequest,
                ],
            ])
        );
    }
}
