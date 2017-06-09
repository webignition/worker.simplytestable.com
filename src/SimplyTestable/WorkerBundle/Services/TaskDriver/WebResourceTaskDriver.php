<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\GuzzleHttp\Exception\CurlException\Exception as CurlException;
use webignition\GuzzleHttp\Exception\CurlException\Factory as CurlExceptionFactory;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResource;
use webignition\WebResource\Exception\Exception as WebResourceException;
use GuzzleHttp\Message\Request as HttpRequest;
use webignition\WebResource\Service\Service as WebResourceService;

abstract class WebResourceTaskDriver extends TaskDriver
{
    const CURL_CODE_INVALID_URL = 3;
    const CURL_CODE_TIMEOUT = 28;
    const CURL_CODE_DNS_LOOKUP_FAILURE = 6;

    /**
     * @var WebResourceException
     */
    private $webResourceException = null;

    /**
     * @var CurlException
     */
    private $curlException = null;

    /**
     * @var WebResource
     */
    protected $webResource;

    /**
     * @var Task
     */
    protected $task;

    /**
     * @var TooManyRedirectsException
     */
    private $tooManyRedirectsException = null;

    /**
     * @var HttpRequest
     */
    private $baseRequest = null;

    /**
     * @var WebResourceService
     */
    protected $webResourceService;

    /**
     * @param WebResourceService $webResourceService
     */
    protected function setWebResourceService(WebResourceService $webResourceService)
    {
        $this->webResourceService = $webResourceService;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Task $task)
    {
        $this->task = $task;

        $this->getHttpClientService()->setUserAgent('ST Web Resource Task Driver (http://bit.ly/RlhKCL)');
        $this->getHttpClientService()->setCookies($this->task->getParameter('cookies'));
        $this->getHttpClientService()->setBasicHttpAuthorization(
            $this->task->getParameter('http-auth-username'),
            $this->task->getParameter('http-auth-password')
        );

        $this->webResource = $this->getWebResource();

        $this->getHttpClientService()->resetUserAgent();
        $this->getHttpClientService()->clearCookies();
        $this->getHttpClientService()->clearBasicHttpAuthorization();

        if (!$this->response->hasSucceeded()) {
            return $this->hasNotSucceededHandler();
        }

        if (!$this->webResource instanceof WebPage) {
            return $this->isNotCorrectWebResourceTypeHandler();
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

    abstract protected function isNotCorrectWebResourceTypeHandler();
    abstract protected function isBlankWebResourceHandler();
    abstract protected function performValidation();

    /**
     * @return HttpRequest
     */
    protected function getBaseRequest()
    {
        if (is_null($this->baseRequest)) {
            $this->baseRequest = $this->getHttpClientService()->get()->createRequest(
                'GET',
                $this->task->getUrl()
            );
        }

        return $this->baseRequest;
    }

    /**
     * @return WebResource
     */
    protected function getWebResource()
    {
        try {
            return $this->webResourceService->get(clone $this->getBaseRequest());
        } catch (WebResourceException $webResourceException) {
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);

            $this->webResourceException = $webResourceException;
        } catch (ConnectException $connectException) {
            $curlExceptionFactory = new CurlExceptionFactory();

            if (!$curlExceptionFactory->isCurlException($connectException)) {
                throw $connectException;
            }

            $curlException = $curlExceptionFactory->fromConnectException($connectException);

            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);

            $this->curlException = $curlException;
        } catch (TooManyRedirectsException $tooManyRedirectsException) {
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);

            $this->tooManyRedirectsException = $tooManyRedirectsException;
        }
    }

    /**
     * @return \stdClass
     */
    protected function getWebResourceExceptionOutput()
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
        if (!empty($this->tooManyRedirectsException)) {
            if ($this->isRedirectLoopException()) {
                return 'Redirect loop detected';
            }

            return 'Redirect limit reached';
        }

        if (!empty($this->curlException)) {
            if (self::CURL_CODE_TIMEOUT == $this->curlException->getCurlCode()) {
                return 'Timeout reached retrieving resource';
            }

            if (self::CURL_CODE_DNS_LOOKUP_FAILURE == $this->curlException->getCurlCode()) {
                return 'DNS lookup failure resolving resource domain name';
            }

            if (self::CURL_CODE_INVALID_URL == $this->curlException->getCurlCode()) {
                return 'Invalid resource URL';
            }

            return '';
        }

        return $this->webResourceException->getResponse()->getReasonPhrase();
    }

    /**
     * @return string
     */
    private function getOutputMessageId()
    {
        if (!empty($this->tooManyRedirectsException)) {
            if ($this->isRedirectLoopException()) {
                return 'redirect-loop';
            }

            return 'redirect-limit-reached';
        }

        if (!empty($this->curlException)) {
            return 'curl-code-' . $this->curlException->getCurlCode();
        }

        return $this->webResourceException->getResponse()->getStatusCode();
    }

    /**
     * @return boolean
     */
    private function isRedirectLoopException()
    {
        $history = $this->getHttpClientService()->getHistory();
        $urlHistory = array();

        $history->getRequests();

        foreach ($history->getRequests(true) as $request) {
            $urlHistory[] = $request->getUrl();
        }

        foreach ($urlHistory as $urlIndex => $url) {
            if (in_array($url, array_slice($urlHistory, $urlIndex + 1))) {
                return true;
            }
        }

        return false;
    }
}
