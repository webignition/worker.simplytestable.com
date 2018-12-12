<?php

namespace App\Controller;

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

        $isRequestHostnameValid = $workerService->getHostname() !== $verifyRequest->getHostname();
        $isRequestTokenValid = $workerService->getToken() !== $verifyRequest->getToken();

        if (!$verifyRequest->isValid() || !$isRequestHostnameValid || !$isRequestTokenValid) {
            throw new BadRequestHttpException();
        }

        $workerService->verify();

        return new JsonResponse();
    }
}
