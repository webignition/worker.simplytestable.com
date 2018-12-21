<?php

namespace App\Services;

use App\Entity\Task\Task;
use App\Model\TaskOutputValues;
use App\Model\TaskPerformerWebPageRetrieverResult;
use GuzzleHttp\Psr7\Request;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\WebPage\WebPage;

class TaskPerformerWebPageRetriever
{
    private $webResourceRetriever;
    private $taskOutputMessageFactory;

    public function __construct(
        WebResourceRetriever $webResourceRetriever,
        TaskOutputMessageFactory $taskOutputMessageFactory
    ) {
        $this->webResourceRetriever = $webResourceRetriever;
        $this->taskOutputMessageFactory = $taskOutputMessageFactory;
    }

    /**
     * @param Task $task
     *
     * @return TaskPerformerWebPageRetrieverResult
     *
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    public function retrieveWebPage(Task $task): TaskPerformerWebPageRetrieverResult
    {
        $result = new TaskPerformerWebPageRetrieverResult();

        $request = new Request('GET', $task->getUrl());

        /* @var WebPage $webPage */
        $webPage = null;

        try {
            $webPage = $this->webResourceRetriever->retrieve($request);
        } catch (InvalidResponseContentTypeException $invalidResponseContentTypeException) {
            $result->setTaskState(Task::STATE_SKIPPED);
            $result->setTaskOutputValues(new TaskOutputValues('', 0, 0));

            return $result;
        } catch (HttpException $httpException) {
            $output = $this->taskOutputMessageFactory->createOutputMessageCollectionFromExceptions(
                $httpException,
                null
            );

            $result->setTaskState(Task::STATE_FAILED_NO_RETRY_AVAILABLE);
            $result->setTaskOutputValues(new TaskOutputValues($output, 1, 0));

            return $result;
        } catch (TransportException $transportException) {
            if (!$transportException->isCurlException() && !$transportException->isTooManyRedirectsException()) {
                throw $transportException;
            }

            $output = $this->taskOutputMessageFactory->createOutputMessageCollectionFromExceptions(
                null,
                $transportException
            );

            $result->setTaskState(Task::STATE_FAILED_NO_RETRY_AVAILABLE);
            $result->setTaskOutputValues(new TaskOutputValues($output, 1, 0));

            return $result;
        }

        if (empty($webPage->getContent())) {
            $result->setTaskState(Task::STATE_SKIPPED);
            $result->setTaskOutputValues(new TaskOutputValues('', 0, 0));

            return $result;
        }

        $result->setTaskState($task->getState());
        $result->setWebPage($webPage);

        return $result;
    }
}
