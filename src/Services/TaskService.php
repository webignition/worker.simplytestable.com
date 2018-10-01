<?php

namespace App\Services;

use App\Model\Task\Type;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use App\Entity\Task\Task;
use Psr\Log\LoggerInterface;
use App\Repository\TaskRepository;
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
     * @var CoreApplicationHttpClient
     */
    private $coreApplicationHttpClient;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        CoreApplicationHttpClient $coreApplicationHttpClient,
        TaskTypeService $taskTypeFactory
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->coreApplicationHttpClient = $coreApplicationHttpClient;
        $this->taskTypeService = $taskTypeFactory;

        $this->taskRepository = $entityManager->getRepository(Task::class);
    }

    public function create(string $url, Type $type, string $parameters): Task
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

        if (empty($task)) {
            return null;
        }

        $taskTypeName = $this->taskRepository->getTypeById($task->getId());

        if (empty($taskTypeName)) {
            return null;
        }

        $task->setType($this->taskTypeService->get($taskTypeName));

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

    /**
     * @param Task $task
     */
    public function cancel(Task $task)
    {
        $taskStateName = $task->getState();

        $isCancelled = Task::STATE_CANCELLED === $taskStateName;
        $isCompleted = Task::STATE_COMPLETED === $taskStateName;

        if (!($isCancelled || $isCompleted)) {
            $task->setState(Task::STATE_CANCELLED);

            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }
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
