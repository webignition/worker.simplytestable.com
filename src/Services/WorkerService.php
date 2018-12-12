<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use App\Entity\ThisWorker;
use Psr\Log\LoggerInterface;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class WorkerService
{
    const STATE_ACTIVE = 'active';
    const STATE_AWAITING_ACTIVATION_VERIFICATION = 'awaiting-activation-verification';
    const STATE_NEW = 'new';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $salt;

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
        string $salt,
        string $hostname,
        string $token,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        CoreApplicationHttpClient $coreApplicationHttpClient,
        ApplicationState $applicationState
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->salt = $salt;
        $this->hostname = $hostname;
        $this->token = $token;
        $this->coreApplicationHttpClient = $coreApplicationHttpClient;
        $this->applicationState = $applicationState;

        $this->entityRepository = $entityManager->getRepository(ThisWorker::class);
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
     * @return ThisWorker
     */
    public function get()
    {
        $workers = $this->entityRepository->findAll();
        if (empty($workers)) {
            $this->create();
            $workers = $this->entityRepository->findAll();
        }

        return $workers[0];
    }

    /**
     * @return ThisWorker
     */
    private function create()
    {
        $this->applicationState->set(ApplicationState::STATE_NEW);

        $thisWorker = new ThisWorker();
        $thisWorker->setHostname($this->hostname);
        $thisWorker->setState(ThisWorker::STATE_NEW);
        $thisWorker->setActivationToken(md5($this->salt . $this->hostname));

        $this->entityManager->persist($thisWorker);
        $this->entityManager->flush();

        return $thisWorker;
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
