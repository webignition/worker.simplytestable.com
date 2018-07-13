<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ThisWorker;
use AppBundle\Request\VerifyRequest;
use AppBundle\Services\Request\Factory\VerifyRequestFactory;
use AppBundle\Services\WorkerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class VerifyController extends AbstractController
{
    /**
     * @param WorkerService $workerService
     * @param VerifyRequestFactory $verifyRequestFactory
     *
     * @return JsonResponse
     */
    public function indexAction(WorkerService $workerService, VerifyRequestFactory $verifyRequestFactory)
    {
        if ($workerService->isMaintenanceReadOnly()) {
            throw new ServiceUnavailableHttpException();
        }

        $verifyRequest = $verifyRequestFactory->create();

        if (!$verifyRequest->isValid()) {
            throw new BadRequestHttpException();
        }

        if (!$this->doesVerifyRequestMatchWorker($workerService->get(), $verifyRequest)) {
            throw new BadRequestHttpException();
        }

        $workerService->verify();

        return new JsonResponse();
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
