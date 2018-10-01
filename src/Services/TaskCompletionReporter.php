<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use App\Entity\Task\Task;
use Psr\Log\LoggerInterface;
use webignition\GuzzleHttp\Exception\CurlException\Factory as CurlExceptionFactory;

class TaskCompletionReporter
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CoreApplicationHttpClient
     */
    private $coreApplicationHttpClient;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        CoreApplicationHttpClient $coreApplicationHttpClient
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->coreApplicationHttpClient = $coreApplicationHttpClient;
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
}
