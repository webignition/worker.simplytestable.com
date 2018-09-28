<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use App\Entity\Task\Task;
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
     * @var TaskTypeFactory
     */
    private $taskTypeFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        WorkerService $workerService,
        CoreApplicationHttpClient $coreApplicationHttpClient,
        TaskTypeFactory $taskTypeFactory
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->workerService = $workerService;
        $this->coreApplicationHttpClient = $coreApplicationHttpClient;
        $this->taskTypeFactory = $taskTypeFactory;

        $this->taskRepository = $entityManager->getRepository(Task::class);
    }

    public function create($url, string $type, string $parameters): Task
    {
        $task = new Task();

        $this->setQueued($task);
        $task->setType($this->taskTypeFactory->create($type));
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

        if (empty($task)) {
            return null;
        }

        $taskTypeName = $this->taskRepository->getTypeById($task->getId());

        if (empty($taskTypeName)) {
            return null;
        }

        $task->setType($this->taskTypeFactory->create($taskTypeName));

        return $task;
    }

    /**
     * @return int[]
     */
    public function getQueuedTaskIds()
    {
        return $this->taskRepository->getIdsByState(Task::STATE_QUEUED);
    }

    public function setQueued(Task $task)
    {
        $task->setState(Task::STATE_QUEUED);
    }

    public function setInProgress(Task $task)
    {
        $task->setState(Task::STATE_IN_PROGRESS);
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

    private function getCompletionStateFromTaskDriverResponse(TaskDriverResponse $taskDriverResponse): string
    {
        if ($taskDriverResponse->hasBeenSkipped()) {
            return Task::STATE_SKIPPED;
        }

        if ($taskDriverResponse->hasSucceeded()) {
            return Task::STATE_COMPLETED;
        }

        return Task::STATE_FAILED_NO_RETRY_AVAILABLE;
    }

    /**
     * @param Task $task
     */
    public function cancel(Task $task)
    {
        $taskStateName = $task->getState();

        $isCancelled = Task::STATE_CANCELLED === $taskStateName;
        $isCompleted = Task::STATE_COMPLETED === $taskStateName;

        if (!($isCancelled || $isCompleted)) {
            $this->finish($task, Task::STATE_CANCELLED);
        }
    }

    private function finish(Task $task, string $state)
    {
        $task->setState($state);

        $this->entityManager->persist($task);
        $this->entityManager->flush();
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
                'type' => $task->getType(),
                'parameter_hash' => $task->getParametersHash(),
            ],
            [
                'end_date_time' => $task->getTimePeriod()->getEndDateTime()->format('c'),
                'output' => $task->getOutput()->getOutput(),
                'contentType' => $task->getOutput()->getContentType(),
                'state' => 'task-' . $task->getState(),
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
            Task::STATE_QUEUED,
            Task::STATE_IN_PROGRESS
        ]);
    }
}
