<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use GuzzleHttp\Psr7\Request;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Model\HttpAuthenticationCredentials;
use SimplyTestable\WorkerBundle\Services\FooHttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResource;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResourceInterfaces\WebResourceInterface;

abstract class WebResourceTaskDriver extends TaskDriver
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
     * @var WebResource
     */
    protected $webResource;

    /**
     * @var Task
     */
    protected $task;

    /**
     * @var WebResourceRetriever
     */
    protected $webResourceRetriever;

    /**
     * @var FooHttpClientService
     */
    protected $fooHttpClientService;

    /**
     * @param StateService $stateService
     * @param FooHttpClientService $fooHttpClientService
     * @param WebResourceRetriever $webResourceRetriever
     */
    public function __construct(
        StateService $stateService,
        FooHttpClientService $fooHttpClientService,
        WebResourceRetriever $webResourceRetriever
    ) {
        parent::__construct($stateService);

        $this->fooHttpClientService = $fooHttpClientService;
        $this->webResourceRetriever = $webResourceRetriever;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    public function execute(Task $task)
    {
        $this->task = $task;

        $this->fooHttpClientService->setCookies($this->task->getParameter('cookies'));
        $this->fooHttpClientService->setBasicHttpAuthorization(new HttpAuthenticationCredentials(
            $this->task->getParameter('http-auth-username'),
            $this->task->getParameter('http-auth-password'),
            'example.com'
        ));
        $this->fooHttpClientService->setRequestHeader('User-Agent', self::USER_AGENT);

        $this->webResource = $this->getWebResource();

        if (!$this->response->hasSucceeded()) {
            return $this->hasNotSucceededHandler();
        }

        if (!$this->webResource instanceof WebPage) {
            $this->response->setHasBeenSkipped();
            $this->response->setIsRetryable(false);
            $this->response->setErrorCount(0);

            return null;
        }

        if (empty($this->webResource->getContent())) {
            return $this->isBlankWebResourceHandler();
        }

        return $this->performValidation();
    }

    /**
     * @return string
     */
    abstract protected function hasNotSucceededHandler();

    abstract protected function isBlankWebResourceHandler();
    abstract protected function performValidation();

    /**
     * @return WebResourceInterface
     *
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    protected function getWebResource()
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

            // bad request, bad response, too many redirects, ...
        } catch (TransportException $transportException) {
            if (!$transportException->isCurlException() && !$transportException->isTooManyRedirectsException()) {
                throw $transportException;
            }

            $this->transportException = $transportException;
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
            // curl things ...
        }
        return null;

//        try {
//            $request = $this->getHttpClientService()->get()->createRequest(
//                'GET',
//                $this->task->getUrl()
//            );

//            return $this->webResourceRetriever->retrieve($request);
//        } catch (WebResourceException $webResourceException) {
//            $this->response->setHasFailed();
//            $this->response->setIsRetryable(false);
//
//            $this->webResourceException = $webResourceException;
//        } catch (ConnectException $connectException) {
//            $curlExceptionFactory = new CurlExceptionFactory();
//
//            if (!$curlExceptionFactory->isCurlException($connectException)) {
//                throw $connectException;
//            }
//
//            $curlException = $curlExceptionFactory->fromConnectException($connectException);
//
//            $this->response->setHasFailed();
//            $this->response->setIsRetryable(false);
//
//            $this->curlException = $curlException;
//        } catch (TooManyRedirectsException $tooManyRedirectsException) {
//            $this->response->setHasFailed();
//            $this->response->setIsRetryable(false);
//
//            $this->tooManyRedirectsException = $tooManyRedirectsException;
//        }
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
        $httpHistory = $this->fooHttpClientService->getHistory();

        $responses = $httpHistory->getResponses();
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

        $requestUrls = $httpHistory->getRequestUrlsAsStrings();
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
