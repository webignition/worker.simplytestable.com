<?php

namespace App\Services;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use GuzzleHttp\Psr7\Request;
use webignition\InternetMediaType\InternetMediaType;
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
     * @return WebPage|null
     *
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    public function retrieveWebPage(Task $task)
    {
        $request = new Request('GET', $task->getUrl());

        /* @var WebPage $webPage */
        $webPage = null;

        try {
            $webPage = $this->webResourceRetriever->retrieve($request);
        } catch (InvalidResponseContentTypeException $invalidResponseContentTypeException) {
            return $this->setTaskOutputAndState(
                $task,
                '',
                Task::STATE_SKIPPED,
                0
            );
        } catch (HttpException $httpException) {
            $output = $this->taskOutputMessageFactory->createOutputMessageCollectionFromExceptions(
                $httpException,
                null
            );

            return $this->setTaskOutputAndState(
                $task,
                json_encode($output),
                Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                1
            );
        } catch (TransportException $transportException) {
            if (!$transportException->isCurlException() && !$transportException->isTooManyRedirectsException()) {
                throw $transportException;
            }

            $output = $this->taskOutputMessageFactory->createOutputMessageCollectionFromExceptions(
                null,
                $transportException
            );

            return $this->setTaskOutputAndState(
                $task,
                json_encode($output),
                Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                1
            );
        }

        if (empty($webPage->getContent())) {
            return $this->setTaskOutputAndState(
                $task,
                '',
                Task::STATE_SKIPPED,
                0
            );
        }

        return $webPage;
    }

    private function setTaskOutputAndState(Task $task, string $output, string $state, int $errorCount)
    {
        $task->setOutput(Output::create(
            $output,
            new InternetMediaType('application/json'),
            $errorCount
        ));

        $task->setState($state);

        return null;
    }
}
