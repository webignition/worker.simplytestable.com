<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\State;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;
use SimplyTestable\WorkerBundle\Model\TaskDriver\Response as TaskDriverResponse;
use SimplyTestable\WorkerBundle\Repository\TaskRepository;
use SimplyTestable\WorkerBundle\Services\TaskDriver\TaskDriver;
use GuzzleHttp\Exception\BadResponseException as HttpBadResponseException;
use GuzzleHttp\Exception\ConnectException as HttpConnectException;
use webignition\GuzzleHttp\Exception\CurlException\Factory as CurlExceptionFactory;

class TaskService extends EntityService
{
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\Task\Task';
    const TASK_STARTING_STATE = 'task-queued';
    const TASK_IN_PROGRESS_STATE = 'task-in-progress';
    const TASK_COMPLETED_STATE = 'task-completed';
    const TASK_CANCELLED_STATE = 'task-cancelled';
    const TASK_FAILED_NO_RETRY_AVAILABLE_STATE = 'task-failed-no-retry-available';
    const TASK_FAILED_RETRY_AVAILABLE_STATE = 'task-failed-retry-available';
    const TASK_FAILED_RETRY_LIMIT_REACHED_STATE = 'task-failed-retry-limit-reached';
    const TASK_SKIPPED_STATE = 'task-skipped';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var UrlService $urlService
     */
    private $urlService;

    /**
     * @var CoreApplicationRouter
     */
    private $coreApplicationRouter;

    /**
     * @var WorkerService
     */
    private $workerService;

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var TaskDriver[]
     */
    private $taskDrivers;

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return self::ENTITY_NAME;
    }

    /**
     * @param EntityManager $entityManager
     * @param LoggerInterface $logger
     * @param StateService $stateService
     * @param UrlService $urlService
     * @param CoreApplicationRouter $coreApplicationRouter
     * @param WorkerService $workerService
     * @param HttpClientService $httpClientService
     */
    public function __construct(
        EntityManager $entityManager,
        LoggerInterface $logger,
        StateService $stateService,
        UrlService $urlService,
        CoreApplicationRouter $coreApplicationRouter,
        WorkerService $workerService,
        HttpClientService $httpClientService
    ) {
        parent::__construct($entityManager);

        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->urlService = $urlService;
        $this->coreApplicationRouter = $coreApplicationRouter;
        $this->workerService = $workerService;
        $this->httpClientService = $httpClientService;
    }

    /**
     * @param string $url
     * @param TaskType $type
     * @param string $parameters
     *
     * @return Task
     */
    public function create($url, TaskType $type, $parameters)
    {
        $task = new Task();
        $task->setState($this->getStartingState());
        $task->setType($type);
        $task->setUrl($url);
        $task->setParameters($parameters);

        if ($this->has($task)) {
            return $this->fetch($task);
        }

        return $task;
    }

    /**
     * @param Task $task
     *
     * @return Task
     */
    private function fetch(Task $task)
    {
        /* @var $task Task */
        $task = $this->getEntityRepository()->findOneBy(array(
            'state' => $task->getState(),
            'type' => $task->getType(),
            'url' => $task->getUrl()
        ));

        return $task;
    }

    /**
     * @param int $id
     *
     * @return Task
     */
    public function getById($id)
    {
        /* @var $task Task */
        $task = $this->getEntityRepository()->find($id);

        return $task;
    }

    /**
     * @param Task $task
     *
     * @return boolean
     */
    private function has(Task $task)
    {
        return !is_null($this->fetch($task));
    }

    /**
     * @param Task $task
     *
     * @return Task
     */
    public function persistAndFlush(Task $task)
    {
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();
        return $task;
    }

    /**
     * @return State
     */
    public function getStartingState()
    {
        return $this->stateService->fetch(self::TASK_STARTING_STATE);
    }

    /**
     * @return State
     */
    public function getQueuedState()
    {
        return $this->getStartingState();
    }

    /**
     * @return State
     */
    public function getInProgressState()
    {
        return $this->stateService->fetch(self::TASK_IN_PROGRESS_STATE);
    }

    /**
     * @return State
     */
    public function getCompletedState()
    {
        return $this->stateService->fetch(self::TASK_COMPLETED_STATE);
    }

    /**
     * @return State
     */
    public function getCancelledState()
    {
        return $this->stateService->fetch(self::TASK_CANCELLED_STATE);
    }

    /**
     * @return State
     */
    public function getFailedNoRetryAvailableState()
    {
        return $this->stateService->fetch(self::TASK_FAILED_NO_RETRY_AVAILABLE_STATE);
    }

    /**
     * @return State
     */
    public function getFailedRetryAvailableState()
    {
        return $this->stateService->fetch(self::TASK_FAILED_RETRY_AVAILABLE_STATE);
    }

    /**
     * @return State
     */
    public function getFailedRetryLimitReachedState()
    {
        return $this->stateService->fetch(self::TASK_FAILED_RETRY_LIMIT_REACHED_STATE);
    }

    /**
     * @return State
     */
    public function getSkippedState()
    {
        return $this->stateService->fetch(self::TASK_SKIPPED_STATE);
    }

    /**
     * @param Task $task
     *
     * @return int
     */
    public function perform(Task $task)
    {
        if (TaskService::TASK_STARTING_STATE != $task->getState()) {
            $this->logger->info(sprintf(
                'TaskService::perform: [%d] Task state is [%s] and cannot be performed',
                $task->getId(),
                $task->getState()
            ));

            return 1;
        }

        $this->logger->info(sprintf(
            'TaskService::perform: [%d] [%s] Initialising',
            $task->getId(),
            $task->getState()
        ));

        $taskDriver = $this->taskDrivers[strtolower($task->getType())];

        $this->start($task);

        $taskDriverResponse = $taskDriver->perform($task);

        if (!$task->getTimePeriod()->hasEndDateTime()) {
            $task->getTimePeriod()->setEndDateTime(new \DateTime());
        }

        $task->setOutput($taskDriverResponse->getTaskOutput());

        $this->finish(
            $task,
            $this->getCompletionStateFromTaskDriverResponse($taskDriverResponse)
        );

        return 0;
    }

    /**
     * @param string $taskTypeName
     * @param TaskDriver $taskDriver
     */
    public function addTaskDriver($taskTypeName, TaskDriver $taskDriver)
    {
        $this->taskDrivers[strtolower($taskTypeName)] = $taskDriver;
    }

    /**
     * @param Task $task
     *
     * @return Task
     */
    private function start(Task $task)
    {
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $task->setTimePeriod($timePeriod);
        $task->setState($this->getInProgressState());

        return $this->persistAndFlush($task);
    }

    /**
     * @param TaskDriverResponse $taskDriverResponse
     *
     * @return State
     */
    private function getCompletionStateFromTaskDriverResponse(TaskDriverResponse $taskDriverResponse)
    {
        if ($taskDriverResponse->hasBeenSkipped()) {
            return $this->getSkippedState();
        }

        if ($taskDriverResponse->hasSucceeded()) {
            return $this->getCompletedState();
        }

        return $this->getFailedNoRetryAvailableState();
    }

    /**
     * @param Task $task
     *
     * @return Task
     */
    public function cancel(Task $task)
    {
        $isCancelled = TaskService::TASK_CANCELLED_STATE == $task->getState();
        $isCompleted = TaskService::TASK_COMPLETED_STATE == $task->getState();

        if ($isCancelled || $isCompleted) {
            return $task;
        }

        return $this->finish($task, $this->getCancelledState());
    }

    /**
     * @param Task $task
     *
     * @return boolean
     */
    private function isQueued(Task $task)
    {
        return $task->getState()->equals($this->getStartingState());
    }

    /**
     * @param Task $task
     * @param State $state
     *
     * @return Task
     */
    private function finish(Task $task, State $state)
    {
        $task->setState($state);
        return $this->persistAndFlush($task);
    }

    /**
     * @param Task $task
     *
     * @return boolean|int
     */
    public function reportCompletion(Task $task)
    {
        $this->logger->info(sprintf(
            'TaskService::reportCompletion: Initialising [%d]',
            $task->getId()
        ));

        if (!$task->hasOutput()) {
            $this->logger->info(sprintf(
                'TaskService::reportCompletion: Task state is [%s], we can\'t report back just yet',
                $task->getState()
            ));
            return true;
        }

        $requestUrl = $this->urlService->prepare(
            $this->coreApplicationRouter->generate(
                'task_complete',
                [
                    'url' => $task->getUrl(),
                    'type' => $task->getType()->getName(),
                    'parameter_hash' => $task->getParametersHash(),
                ]
            )
        );

        $httpRequest = $this->httpClientService->postRequest($requestUrl, [
            'body' => [
                'end_date_time' => $task->getTimePeriod()->getEndDateTime()->format('c'),
                'output' => $task->getOutput()->getOutput(),
                'contentType' => (string)$task->getOutput()->getContentType(),
                'state' => $task->getState()->getName(),
                'errorCount' => $task->getOutput()->getErrorCount(),
                'warningCount' => $task->getOutput()->getWarningCount()
            ],
        ]);

        $this->logger->info("TaskService::reportCompletion: Reporting completion state to " . $requestUrl);

        try {
            $response = $this->httpClientService->get()->send($httpRequest);

            $this->logger->notice(sprintf(
                'TaskService::reportCompletion: %s: %s %s',
                $requestUrl,
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
        } catch (HttpBadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();

            if ($response->getStatusCode() !== 410) {
                $this->logger->error(sprintf(
                    'TaskService::reportCompletion: Completion reporting failed for [%i] [%s]',
                    $task->getId(),
                    $task->getUrl()
                ));

                $this->logger->error(sprintf(
                    'TaskService::reportCompletion: [%i] %s: %s %s',
                    $task->getId(),
                    $requestUrl,
                    $response->getStatusCode(),
                    $response->getReasonPhrase()
                ));

                return $response->getStatusCode();
            }
        } catch (HttpConnectException $connectException) {
            $curlExceptionFactory = new CurlExceptionFactory();

            if ($curlExceptionFactory::isCurlException($connectException)) {
                return $curlExceptionFactory::fromConnectException($connectException)->getCurlCode();
            }
        }

        $this->getEntityManager()->remove($task);
        $this->getEntityManager()->remove($task->getOutput());
        $this->getEntityManager()->remove($task->getTimePeriod());
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * @return TaskRepository
     */
    public function getEntityRepository()
    {
        /* @var $entityRepository TaskRepository */
        $entityRepository = parent::getEntityRepository();

        return $entityRepository;
    }

    /**
     * @return int
     */
    public function getInCompleteCount()
    {
        return $this->getEntityRepository()->getCountByStates([
            $this->getQueuedState(),
            $this->getInProgressState()
        ]);
    }
}
