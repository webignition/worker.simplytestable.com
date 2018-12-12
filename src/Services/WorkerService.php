<?php

namespace App\Services;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class WorkerService
{
    const STATE_ACTIVE = 'active';
    const STATE_AWAITING_ACTIVATION_VERIFICATION = 'awaiting-activation-verification';
    const STATE_NEW = 'new';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var string
     */
    private $token;

    /**
     * @var CoreApplicationHttpClient
     */
    private $coreApplicationHttpClient;

    /**
     * @var ApplicationState
     */
    private $applicationState;

    public function __construct(
        string $hostname,
        string $token,
        LoggerInterface $logger,
        CoreApplicationHttpClient $coreApplicationHttpClient,
        ApplicationState $applicationState
    ) {
        $this->logger = $logger;
        $this->hostname = $hostname;
        $this->token = $token;
        $this->coreApplicationHttpClient = $coreApplicationHttpClient;
        $this->applicationState = $applicationState;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Issue activation request to core application
     * Activation is completed when core application verifies
     *
     * @return int
     *
     * @throws GuzzleException
     */
    public function activate()
    {
        $this->logger->info("WorkerService::activate: Initialising");

        if (self::STATE_NEW !== $this->applicationState->get()) {
            $this->logger->info("WorkerService::activate: This worker is not new and cannot be activated");

            return 0;
        }

        $request = $this->coreApplicationHttpClient->createPostRequest(
            'worker_activate',
            [],
            [
                'hostname' => $this->getHostname(),
                'token' => $this->getToken(),
            ]
        );

        $this->logger->info("WorkerService::activate: Requesting activation with " . (string)$request->getUri());

        $response = null;
        $responseCode = null;
        $responsePhrase = null;
        $hasHttpError = false;
        $hasCurlError = false;

        try {
            $response = $this->coreApplicationHttpClient->send($request);
            $responseCode = 200;
            $responsePhrase = $response->getReasonPhrase();
        } catch (BadResponseException $badResponseException) {
            $hasHttpError = true;
            $response = $badResponseException->getResponse();
            $responseCode = $response->getStatusCode();
            $responsePhrase = $response->getReasonPhrase();
        } catch (ConnectException $connectException) {
            $hasCurlError = true;
            $curlException = GuzzleCurlExceptionFactory::fromConnectException($connectException);
            $responseCode = $curlException->getCurlCode();
            $responsePhrase = $curlException->getMessage();
        }

        $this->logger->info(sprintf(
            "WorkerService::activate: %s: %s %s",
            (string)$request->getUri(),
            $responseCode,
            $responsePhrase
        ));

        if ($hasHttpError || $hasCurlError) {
            $this->logger->error("WorkerService::activate: Activation request failed: " . $responseCode);

            return $responseCode;
        }

        $this->applicationState->set(ApplicationState::STATE_AWAITING_ACTIVATION_VERIFICATION);

        return 0;
    }

    public function verify()
    {
        if (self::STATE_AWAITING_ACTIVATION_VERIFICATION === $this->applicationState->get()) {
            $this->applicationState->set(ApplicationState::STATE_ACTIVE);
        }
    }
}
