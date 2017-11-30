<?php

namespace Tests\WorkerBundle\Unit\Controller;

use SimplyTestable\WorkerBundle\Controller\VerifyController;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Request\VerifyRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\WorkerBundle\Factory\MockFactory;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

/**
 * @group Controller/VerifyController
 */
class VerifyControllerTest extends BaseSimplyTestableTestCase
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
