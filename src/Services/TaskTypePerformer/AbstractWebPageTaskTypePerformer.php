<?php

namespace App\Services\TaskTypePerformer;

use GuzzleHttp\Psr7\Request;
use App\Entity\Task\Task;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpClientService;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResourceInterfaces\WebResourceInterface;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

abstract class AbstractWebPageTaskTypePerformer extends TaskTypePerformer
{
    const CURL_CODE_INVALID_URL = 3;
    const CURL_CODE_TIMEOUT = 28;
    const CURL_CODE_DNS_LOOKUP_FAILURE = 6;

    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';

    /**
     * @var HttpException
     */
    private $httpException;

    /**
     * @var TransportException
     */
    private $transportException;

    /**
     * @var Task
     */
    protected $task;

    /**
     * @var WebResourceRetriever
     */
    private $webResourceRetriever;

    /**
     * @var HttpClientService
     */
    protected $httpClientService;

    /**
     * @var HttpClientConfigurationService
     */
    private $httpClientConfigurationService;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistoryContainer;

    public function __construct(
        HttpClientService $httpClientService,
        HttpClientConfigurationService $httpClientConfigurationService,
        WebResourceRetriever $webResourceRetriever,
        HttpHistoryContainer $httpHistoryContainer
    ) {
        $this->httpClientService = $httpClientService;
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->webResourceRetriever = $webResourceRetriever;
        $this->httpHistoryContainer = $httpHistoryContainer;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    protected function execute(Task $task)
    {
        $this->task = $task;
        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        $webPage = $this->retrieveWebPage();

        if (!$this->response->hasSucceeded()) {
            return $this->hasNotSucceededHandler();
        }

        if (!$webPage instanceof WebPage) {
            $this->response->setHasBeenSkipped();
            $this->response->setIsRetryable(false);
            $this->response->setErrorCount(0);

            return null;
        }

        if (empty($webPage->getContent())) {
            return $this->isBlankWebResourceHandler();
        }

        return $this->performValidation($webPage);
    }

    /**
     * @return string
     */
    abstract protected function hasNotSucceededHandler();

    abstract protected function isBlankWebResourceHandler();

    /**
     * @param WebPage $webPage
     *
     * @return string
     */
    abstract protected function performValidation(WebPage $webPage);

    /**
     * @return WebResourceInterface
     *
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    private function retrieveWebPage()
    {
        $request = new Request('GET', $this->task->getUrl());

        try {
            return $this->webResourceRetriever->retrieve($request);
        } catch (InvalidResponseContentTypeException $invalidResponseContentTypeException) {
            $this->response->setHasBeenSkipped();
            $this->response->setIsRetryable(false);
            $this->response->setErrorCount(0);
        } catch (HttpException $httpException) {
            $this->httpException = $httpException;
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
        } catch (TransportException $transportException) {
            if (!$transportException->isCurlException() && !$transportException->isTooManyRedirectsException()) {
                throw $transportException;
            }

            $this->transportException = $transportException;
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
        }

        return null;
    }

    /**
     * @return \stdClass
     */
    protected function getHttpExceptionOutput()
    {
        return (object)[
            'messages' => [
                [
                    'message' => $this->getOutputMessage(),
                    'messageId' => 'http-retrieval-' . $this->getOutputMessageId(),
                    'type' => 'error',
                ]
            ],
        ];
    }

    /**
     * @return string
     */
    private function getOutputMessage()
    {
        if ($this->hasTooManyRedirectsException()) {
            if ($this->isRedirectLoopException()) {
                return 'Redirect loop detected';
            }

            return 'Redirect limit reached';
        }

        if (!empty($this->transportException)) {
            if ($this->transportException->isCurlException()) {
                if (self::CURL_CODE_TIMEOUT == $this->transportException->getCode()) {
                    return 'Timeout reached retrieving resource';
                }

                if (self::CURL_CODE_DNS_LOOKUP_FAILURE == $this->transportException->getCode()) {
                    return 'DNS lookup failure resolving resource domain name';
                }

                if (self::CURL_CODE_INVALID_URL == $this->transportException->getCode()) {
                    return 'Invalid resource URL';
                }

                return '';
            }

            return '';
        }

        return $this->httpException->getMessage();
    }

    /**
     * @return string
     */
    private function getOutputMessageId()
    {
        if ($this->hasTooManyRedirectsException()) {
            if ($this->isRedirectLoopException()) {
                return 'redirect-loop';
            }

            return 'redirect-limit-reached';
        }

        if (!empty($this->transportException)) {
            return 'curl-code-' . $this->transportException->getCode();
        }

        if (!empty($this->curlException)) {
            return 'curl-code-' . $this->curlException->getCurlCode();
        }

        return $this->httpException->getCode();
    }

    /**
     * @return boolean
     */
    private function isRedirectLoopException()
    {
        $responses = $this->httpHistoryContainer->getResponses();
        $responseHistoryContainsOnlyRedirects = true;

        foreach ($responses as $response) {
            $statusCode = $response->getStatusCode();

            if ($statusCode < 300 || $statusCode >=400) {
                $responseHistoryContainsOnlyRedirects = false;
            }
        }

        if (!$responseHistoryContainsOnlyRedirects) {
            return false;
        }

        $requestUrls = $this->httpHistoryContainer->getRequestUrlsAsStrings();
        $requestUrls = array_slice($requestUrls, count($requestUrls) / 2);

        foreach ($requestUrls as $urlIndex => $url) {
            if (in_array($url, array_slice($requestUrls, $urlIndex + 1))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function hasTooManyRedirectsException()
    {
        if (empty($this->transportException)) {
            return false;
        }

        return $this->transportException->isTooManyRedirectsException();
    }
}