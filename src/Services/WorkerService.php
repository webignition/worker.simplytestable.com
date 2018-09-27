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
     * @var CoreApplicationHttpClient
     */
    private $coreApplicationHttpClient;

    public function __construct(
        string $salt,
        string $hostname,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        CoreApplicationHttpClient $coreApplicationHttpClient
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->salt = $salt;
        $this->hostname = $hostname;
        $this->coreApplicationHttpClient = $coreApplicationHttpClient;

        $this->entityRepository = $entityManager->getRepository(ThisWorker::class);
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

        $thisWorker = $this->get();

        if (!$thisWorker->isNew()) {
            $this->logger->info("WorkerService::activate: This worker is not new and cannot be activated");

            return 0;
        }

        $request = $this->coreApplicationHttpClient->createPostRequest(
            'worker_activate',
            [],
            [
                'hostname' => $thisWorker->getHostname(),
                'token' => $thisWorker->getActivationToken()
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

        $this->setState(ThisWorker::STATE_AWAITING_ACTIVATION_VERIFICATION);

        return 0;
    }

    public function setActive()
    {
        $this->setState(ThisWorker::STATE_ACTIVE);
    }

    public function setReadOnly()
    {
        $this->setState(ThisWorker::STATE_MAINTENANCE_READ_ONLY);
    }

    /**
     * @param string $stateName
     */
    private function setState($stateName)
    {
        $thisWorker = $this->get();
        $thisWorker->setState($stateName);

        $this->entityManager->persist($thisWorker);
        $this->entityManager->flush();
    }

    public function verify()
    {
        $thisWorker = $this->get();

        if (!$thisWorker->isAwaitingActivationVerification()) {
            $this->logger->info("WorkerService::verify: This worker is not awaiting activation verification");

            return true;
        }

        $this->setState(ThisWorker::STATE_ACTIVE);

        return true;
    }
}
