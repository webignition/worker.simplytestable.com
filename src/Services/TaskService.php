<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use App\Entity\Task\Task;
use App\Entity\State;
use App\Entity\Task\Type\Type as TaskType;
use Psr\Log\LoggerInterface;
use App\Entity\TimePeriod;
use App\Model\TaskDriver\Response as TaskDriverResponse;
use App\Repository\TaskRepository;
use App\Services\TaskDriver\TaskDriver;
use webignition\GuzzleHttp\Exception\CurlException\Factory as CurlExceptionFactory;

class TaskService
{
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

        $this->setQueued($task);
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
     * @return int[]
     */
    public function getQueuedTaskIds()
    {
        return $this->taskRepository->getIdsByState($this->stateService->fetch(Task::STATE_QUEUED));
    }

    /**
     * @param Task $task
     */
    public function setQueued(Task $task)
    {
        $task->setState($this->stateService->fetch(Task::STATE_QUEUED));
    }

    /**
     * @param Task $task
     */
    public function setInProgress(Task $task)
    {
        $task->setState($this->stateService->fetch(Task::STATE_IN_PROGRESS));
    }

    /**
     * @param Task $task
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
        $this->setInProgress($task);

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
            return $this->stateService->fetch(Task::STATE_SKIPPED);
        }

        if ($taskDriverResponse->hasSucceeded()) {
            return $this->stateService->fetch(Task::STATE_COMPLETED);
        }

        return $this->stateService->fetch(Task::STATE_FAILED_NO_RETRY_AVAILABLE);
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

        return $this->finish($task, $this->stateService->fetch(Task::STATE_CANCELLED));
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
                'url' => base64_encode($task->getUrl()),
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
            $this->stateService->fetch(Task::STATE_QUEUED),
            $this->stateService->fetch(Task::STATE_IN_PROGRESS)
        ]);
    }
}
