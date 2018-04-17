<?php

namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\State;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;
use SimplyTestable\WorkerBundle\Model\TaskDriver\Response as TaskDriverResponse;
use SimplyTestable\WorkerBundle\Repository\TaskRepository;
use SimplyTestable\WorkerBundle\Services\TaskDriver\TaskDriver;
use webignition\GuzzleHttp\Exception\CurlException\Factory as CurlExceptionFactory;

class TaskService
{
    const TASK_FAILED_RETRY_LIMIT_REACHED_STATE = 'task-failed-retry-limit-reached';
    const TASK_SKIPPED_STATE = 'task-skipped';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var WorkerService
     */
    private $workerService;

    /**
     * @var TaskDriver[]
     */
    private $taskDrivers;

    /**
     * @var CoreApplicationHttpClient
     */
    private $coreApplicationHttpClient;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     * @param StateService $stateService
     * @param WorkerService $workerService
     * @param CoreApplicationHttpClient $coreApplicationHttpClient
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        StateService $stateService,
        WorkerService $workerService,
        CoreApplicationHttpClient $coreApplicationHttpClient
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->workerService = $workerService;

        $this->coreApplicationHttpClient = $coreApplicationHttpClient;
        $this->taskRepository = $entityManager->getRepository(Task::class);
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

        $existingTask = $this->taskRepository->findOneBy([
            'state' => $task->getState(),
            'type' => $task->getType(),
            'url' => $task->getUrl()
        ]);

        if (!empty($existingTask)) {
            return $existingTask;
        }

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
        $task = $this->taskRepository->find($id);

        return $task;
    }

    /**
     * @return State
     */
    public function getStartingState()
    {
        return $this->stateService->fetch(Task::STATE_QUEUED);
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
        return $this->stateService->fetch(Task::STATE_IN_PROGRESS);
    }

    /**
     * @return State
     */
    public function getCompletedState()
    {
        return $this->stateService->fetch(Task::STATE_COMPLETED);
    }

    /**
     * @return State
     */
    public function getCancelledState()
    {
        return $this->stateService->fetch(Task::STATE_CANCELLED);
    }

    /**
     * @return State
     */
    public function getFailedNoRetryAvailableState()
    {
        return $this->stateService->fetch(Task::STATE_FAILED_NO_RETRY_AVAILABLE);
    }

    /**
     * @return State
     */
    public function getFailedRetryAvailableState()
    {
        return $this->stateService->fetch(Task::STATE_FAILED_RETRY_AVAILABLE);
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

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
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
        $taskStateName = $task->getState()->getName();

        $isCancelled = Task::STATE_CANCELLED === $taskStateName;
        $isCompleted = Task::STATE_COMPLETED === $taskStateName;

        if ($isCancelled || $isCompleted) {
            return $task;
        }

        return $this->finish($task, $this->getCancelledState());
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

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    /**
     * @param Task $task
     *
     * @return boolean|int
     *
     * @throws GuzzleException
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

        $request = $this->coreApplicationHttpClient->createPostRequest(
            'task_complete',
            [
                'url' => $task->getUrl(),
                'type' => $task->getType()->getName(),
                'parameter_hash' => $task->getParametersHash(),
            ],
            [
                'end_date_time' => $task->getTimePeriod()->getEndDateTime()->format('c'),
                'output' => $task->getOutput()->getOutput(),
                'contentType' => (string)$task->getOutput()->getContentType(),
                'state' => $task->getState()->getName(),
                'errorCount' => $task->getOutput()->getErrorCount(),
                'warningCount' => $task->getOutput()->getWarningCount()
            ]
        );

        try {
            $response = $this->coreApplicationHttpClient->send($request);

            $this->logger->notice(sprintf(
                'TaskService::reportCompletion: %s: %s %s',
                (string)$request->getUri(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
        } catch (ConnectException $connectException) {
            $curlExceptionFactory = new CurlExceptionFactory();

            if ($curlExceptionFactory::isCurlException($connectException)) {
                return $curlExceptionFactory::fromConnectException($connectException)->getCurlCode();
            }

            throw $connectException;
        } catch (BadResponseException $badResponseException) {
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
                    (string)$request->getUri(),
                    $response->getStatusCode(),
                    $response->getReasonPhrase()
                ));

                return $response->getStatusCode();
            }
        }

        $this->entityManager->remove($task);
        $this->entityManager->remove($task->getOutput());
        $this->entityManager->remove($task->getTimePeriod());
        $this->entityManager->flush();

        return true;
    }

    /**
     * @return int
     */
    public function getInCompleteCount()
    {
        return $this->taskRepository->getCountByStates([
            $this->getQueuedState(),
            $this->getInProgressState()
        ]);
    }
}
