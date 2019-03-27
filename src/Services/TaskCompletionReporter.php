<?php

namespace App\Services;

use App\Event\TaskEvent;
use App\Event\TaskReportCompletionFailureEvent;
use App\Event\TaskReportCompletionSuccessEvent;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use App\Entity\Task\Task;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use webignition\GuzzleHttp\Exception\CurlException\Factory as CurlExceptionFactory;

class TaskCompletionReporter
{
    private $coreApplicationHttpClient;
    private $eventDispatcher;

    public function __construct(
        CoreApplicationHttpClient $coreApplicationHttpClient,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->coreApplicationHttpClient = $coreApplicationHttpClient;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Task $task
     *
     * @return bool
     *
     * @throws GuzzleException
     */
    public function reportCompletion(Task $task)
    {
        $taskOutput = $task->getOutput();

        if (null === $taskOutput) {
            return true;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $endDateTime = $task->getEndDateTime() ?? new \DateTime();

        $request = $this->coreApplicationHttpClient->createPostRequest(
            'task_complete',
            [
                'url' => base64_encode($task->getUrl()),
                'type' => (string)$task->getType(),
                'parameter_hash' => $task->getParametersHash(),
            ],
            [
                'end_date_time' => $endDateTime->format('c'),
                'output' => $taskOutput->getContent(),
                'contentType' => $taskOutput->getContentType(),
                'state' => 'task-' . $task->getState(),
                'errorCount' => $taskOutput->getErrorCount(),
                'warningCount' => $taskOutput->getWarningCount()
            ]
        );

        try {
            $this->coreApplicationHttpClient->send($request);

            $this->dispatchSuccessEvent($task);
        } catch (ConnectException $connectException) {
            $curlExceptionFactory = new CurlExceptionFactory();

            $failureType = TaskReportCompletionFailureEvent::FAILURE_TYPE_UNKNOWN;
            $statusCode = 0;

            $curlException = $curlExceptionFactory::fromConnectException($connectException);
            if ($curlException) {
                $failureType = TaskReportCompletionFailureEvent::FAILURE_TYPE_CURL;
                $statusCode = $curlException->getCurlCode();
            }

            $this->dispatchFailureEvent($task, $failureType, $statusCode, (string) $request->getUri());

            return false;
        } catch (BadResponseException $badResponseException) {
            $failureType = TaskReportCompletionFailureEvent::FAILURE_TYPE_UNKNOWN;
            $failureCode = 0;

            $response = $badResponseException->getResponse();

            if ($response instanceof Response) {
                if (410 === $response->getStatusCode()) {
                    $this->dispatchSuccessEvent($task);

                    return true;
                }

                $failureType = TaskReportCompletionFailureEvent::FAILURE_TYPE_HTTP;
                $failureCode = $response->getStatusCode();
            }

            $this->dispatchFailureEvent($task, $failureType, $failureCode, (string) $request->getUri());

            return false;
        }

        return true;
    }

    private function dispatchSuccessEvent(Task $task)
    {
        $this->eventDispatcher->dispatch(
            TaskEvent::TYPE_REPORTED_COMPLETION,
            new TaskReportCompletionSuccessEvent($task)
        );
    }

    private function dispatchFailureEvent(Task $task, string $failureType, int $statusCode, string $requestUrl)
    {
        $this->eventDispatcher->dispatch(
            TaskEvent::TYPE_REPORTED_COMPLETION,
            new TaskReportCompletionFailureEvent($task, $failureType, $statusCode, $requestUrl)
        );
    }
}
