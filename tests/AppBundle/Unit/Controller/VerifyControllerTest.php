<?php

namespace Tests\AppBundle\Unit\Controller;

use AppBundle\Controller\VerifyController;
use AppBundle\Entity\ThisWorker;
use AppBundle\Request\VerifyRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\AppBundle\Factory\MockFactory;

/**
 * @group Controller/VerifyController
 */
class VerifyControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testIndexActionInMaintenanceReadOnlyMode()
    {
        $this->expectException(ServiceUnavailableHttpException::class);

        $verifyController = new VerifyController();
        $verifyController->indexAction(
            MockFactory::createWorkerService([
                'isMaintenanceReadOnly' => [
                    'return' => true,
                ],
            ]),
            MockFactory::createVerifyRequestFactory()
        );
    }

    public function testIndexActionWithInvalidRequest()
    {
        $verifyRequest = new VerifyRequest(null, null);

        $this->expectException(BadRequestHttpException::class);

        $verifyController = new VerifyController();
        $verifyController->indexAction(
            MockFactory::createWorkerService([
                'isMaintenanceReadOnly' => [
                    'return' => false,
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
        $verifyRequest = new VerifyRequest('foo', 'bar');
        $worker = new ThisWorker();

        $this->expectException(BadRequestHttpException::class);

        $verifyController = new VerifyController();
        $verifyController->indexAction(
            MockFactory::createWorkerService([
                'isMaintenanceReadOnly' => [
                    'return' => false,
                ],
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
