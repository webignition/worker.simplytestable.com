<?php

namespace App\Controller;

use App\Entity\ThisWorker;
use App\Request\VerifyRequest;
use App\Services\Request\Factory\VerifyRequestFactory;
use App\Services\WorkerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
