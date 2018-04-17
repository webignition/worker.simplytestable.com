<?php

namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use Psr\Log\LoggerInterface;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class WorkerService
{
    const WORKER_NEW_STATE = 'worker-new';
    const WORKER_ACTIVE_STATE = 'worker-active';
    const WORKER_AWAITING_ACTIVATION_VERIFICATION_STATE = 'worker-awaiting-activation-verification';
    const WORKER_ACTIVATE_REMOTE_ENDPOINT_IDENTIFIER = 'worker-activate';
    const WORKER_MAINTENANCE_READ_ONLY_STATE = 'worker-maintenance-read-only';

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
     * @var StateService
     */
    private $stateService;

    /**
     * @var CoreApplicationHttpClient
     */
    private $coreApplicationHttpClient;


    /**
     * @param EntityManager $entityManager
     * @param LoggerInterface $logger
     * @param string $salt
     * @param string $hostname
     * @param StateService $stateService
     * @param CoreApplicationHttpClient $coreApplicationHttpClient
     */
    public function __construct(
        EntityManager $entityManager,
        LoggerInterface $logger,
        $salt,
        $hostname,
        StateService $stateService,
        CoreApplicationHttpClient $coreApplicationHttpClient
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->salt = $salt;
        $this->hostname = $hostname;
        $this->stateService = $stateService;
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
        $thisWorker->setState($this->stateService->fetch('worker-new'));
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

        if (!$this->isNew()) {
            $this->logger->info("WorkerService::activate: This worker is not new and cannot be activated");

            return 0;
        }

        $thisWorker = $this->get();

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

        $thisWorker->setNextState();
        $this->entityManager->persist($thisWorker);
        $this->entityManager->flush();

        return 0;
    }

    public function setActive()
    {
        $this->setState(self::WORKER_ACTIVE_STATE);
    }

    public function setReadOnly()
    {
        $this->setState(self::WORKER_MAINTENANCE_READ_ONLY_STATE);
    }

    /**
     * @param string $stateName
     */
    private function setState($stateName)
    {
        $thisWorker = $this->get();
        $thisWorker->setState(
            $this->stateService->fetch($stateName)
        );

        $this->entityManager->persist($thisWorker);
        $this->entityManager->flush();
    }

    public function verify()
    {
        if (!$this->isAwaitingActivationVerification()) {
            $this->logger->info("WorkerService::verify: This worker is not awaiting activation verification");

            return true;
        }

        $thisWorker = $this->get();
        $thisWorker->setNextState();

        $this->entityManager->persist($thisWorker);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @return bool
     */
    private function isNew()
    {
        return $this->get()->getState()->equals(
            $this->stateService->fetch(self::WORKER_NEW_STATE)
        );
    }

    /**
     * @return bool
     */
    private function isAwaitingActivationVerification()
    {
        return $this->get()->getState()->equals(
            $this->stateService->fetch(self::WORKER_AWAITING_ACTIVATION_VERIFICATION_STATE)
        );
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->get()->getState()->equals(
            $this->stateService->fetch(self::WORKER_ACTIVE_STATE)
        );
    }


    /**
     * @return bool
     */
    public function isMaintenanceReadOnly()
    {
        return $this->get()->getState()->equals(
            $this->stateService->fetch(self::WORKER_MAINTENANCE_READ_ONLY_STATE)
        );
    }
}
