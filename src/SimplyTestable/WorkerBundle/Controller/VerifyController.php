<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Request\VerifyRequest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class VerifyController extends Controller
{
    public function indexAction()
    {
        if ($this->container->get('simplytestable.services.workerservice')->isMaintenanceReadOnly()) {
            throw new ServiceUnavailableHttpException();
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

        return new Response();
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
