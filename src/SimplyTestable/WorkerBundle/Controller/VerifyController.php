<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Request\VerifyRequest;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VerifyController extends BaseController
{
    public function indexAction()
    {
        if ($this->isInMaintenanceReadOnlyMode()) {
            return $this->sendServiceUnavailableResponse();
        }

        $verifyRequest = $this->container->get('simplytestable.services.request.factory.verify')->create();

        if (!$verifyRequest->isValid()) {
            throw new BadRequestHttpException();
        }

        $workerService = $this->container->get('simplytestable.services.workerservice');

        if (!$this->doesVerifyRequestMatchWorker($workerService->get(), $verifyRequest)) {
            throw new BadRequestHttpException();
        }

        $workerService->verify();

        return $this->sendSuccessResponse();
    }

    /**
     * @param ThisWorker $worker
     * @param VerifyRequest $verifyRequest
     *
     * @return bool
     */
    private function doesVerifyRequestMatchWorker(ThisWorker $worker, VerifyRequest $verifyRequest)
    {
        if ($worker->getHostname() != $verifyRequest->getHostname()) {
            return false;
        }

        if ($worker->getActivationToken() != $verifyRequest->getToken()) {
            return false;
        }

        return true;
    }
}
