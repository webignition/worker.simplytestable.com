<?php

namespace App\Controller;

use App\Services\ApplicationConfiguration;
use App\Services\Request\Factory\VerifyRequestFactory;
use App\Services\WorkerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VerifyController extends AbstractController
{
    public function indexAction(
        ApplicationConfiguration $applicationConfiguration,
        WorkerService $workerService,
        VerifyRequestFactory $verifyRequestFactory
    ): JsonResponse {
        $verifyRequest = $verifyRequestFactory->create();

        $isRequestHostnameValid = $applicationConfiguration->getHostname() !== $verifyRequest->getHostname();
        $isRequestTokenValid = $applicationConfiguration->getToken() !== $verifyRequest->getToken();

        if (!$verifyRequest->isValid() || !$isRequestHostnameValid || !$isRequestTokenValid) {
            throw new BadRequestHttpException();
        }

        $workerService->verify();

        return new JsonResponse();
    }
}
