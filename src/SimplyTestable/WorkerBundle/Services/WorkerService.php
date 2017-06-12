<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use Psr\Log\LoggerInterface;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class WorkerService extends EntityService
{
    const WORKER_NEW_STATE = 'worker-new';
    const WORKER_ACTIVE_STATE = 'worker-active';
    const WORKER_AWAITING_ACTIVATION_VERIFICATION_STATE = 'worker-awaiting-activation-verification';
    const WORKER_ACTIVATE_REMOTE_ENDPOINT_IDENTIFIER = 'worker-activate';
    const WORKER_MAINTENANCE_READ_ONLY_STATE = 'worker-maintenance-read-only';
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\ThisWorker';

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
    private $coreApplicationBaseUrl;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var UrlService $urlService
     */
    private $urlService;

    /**
     * @param EntityManager $entityManager
     * @param LoggerInterface $logger
     * @param string $salt
     * @param string $hostname
     * @param string $coreApplicationBaseUrl
     * @param StateService $stateService
     * @param HttpClientService $httpClientService
     * @param UrlService $urlService
     */
    public function __construct(
        EntityManager $entityManager,
        LoggerInterface $logger,
        $salt,
        $hostname,
        $coreApplicationBaseUrl,
        StateService $stateService,
        HttpClientService $httpClientService,
        UrlService $urlService
    ) {
        parent::__construct($entityManager);

        $this->logger = $logger;
        $this->salt = $salt;
        $this->hostname = $hostname;
        $this->coreApplicationBaseUrl = $coreApplicationBaseUrl;
        $this->stateService = $stateService;
        $this->httpClientService = $httpClientService;
        $this->urlService = $urlService;
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return ThisWorker
     */
    public function get()
    {
        $workers = $this->getEntityRepository()->findAll();
        if (empty($workers)) {
            $this->create();
            $workers = $this->getEntityRepository()->findAll();
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

        return $this->persistAndFlush($thisWorker);
    }

    /**
     * @param ThisWorker $thisWorker
     *
     * @return ThisWorker
     */
    private function persistAndFlush(ThisWorker $thisWorker)
    {
        $this->getEntityManager()->persist($thisWorker);
        $this->getEntityManager()->flush();

        return $thisWorker;
    }

    /**
     * Issue activation request to core application
     * Activation is completed when core application verifies
     *
     * @return int
     */
    public function activate()
    {
        $this->logger->info("WorkerService::activate: Initialising");

        if (!$this->isNew()) {
            $this->logger->info("WorkerService::activate: This worker is not new and cannot be activated");

            return 0;
        }

        $thisWorker = $this->get();
        $requestUrl = $this->urlService->prepare($this->coreApplicationBaseUrl . 'worker/activate/');

        $httpRequest = $this->httpClientService->postRequest($requestUrl, [
            'body' => [
                'hostname' => $thisWorker->getHostname(),
                'token' => $thisWorker->getActivationToken()
            ],
        ]);

        $this->logger->info("WorkerService::activate: Requesting activation with " . $requestUrl);

        $response = null;
        $responseCode = null;
        $responsePhrase = null;
        $hasHttpError = false;
        $hasCurlError = false;

        try {
            $response = $this->httpClientService->get()->send($httpRequest);
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
            $requestUrl,
            $responseCode,
            $responsePhrase
        ));

        if ($hasHttpError || $hasCurlError) {
            $this->logger->error("WorkerService::activate: Activation request failed: " . $responseCode);

            return $responseCode;
        }

        $thisWorker->setNextState();
        $this->persistAndFlush($thisWorker);

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

    private function setState($stateName)
    {
        $thisWorker = $this->get();
        $thisWorker->setState(
            $this->stateService->fetch($stateName)
        );
        $this->persistAndFlush($thisWorker);
    }

    public function verify()
    {
        if (!$this->isAwaitingActivationVerification()) {
            $this->logger->info("WorkerService::verify: This worker is not awaiting activation verification");

            return true;
        }

        $thisWorker = $this->get();
        $thisWorker->setNextState();
        $this->persistAndFlush($thisWorker);

        return true;
    }

    /**
     * @return boolean
     */
    private function isNew()
    {
        return $this->get()->getState()->equals(
            $this->stateService->fetch(self::WORKER_NEW_STATE)
        );
    }

    /**
     * @return boolean
     */
    private function isAwaitingActivationVerification()
    {
        return $this->get()->getState()->equals(
            $this->stateService->fetch(self::WORKER_AWAITING_ACTIVATION_VERIFICATION_STATE)
        );
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->get()->getState()->equals(
            $this->stateService->fetch(self::WORKER_ACTIVE_STATE)
        );
    }


    /**
     * @return boolean
     */
    public function isMaintenanceReadOnly()
    {
        return $this->get()->getState()->equals(
            $this->stateService->fetch(self::WORKER_MAINTENANCE_READ_ONLY_STATE)
        );
    }
}
