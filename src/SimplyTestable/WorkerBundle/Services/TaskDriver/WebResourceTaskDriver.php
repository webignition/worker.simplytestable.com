<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\GuzzleHttp\Exception\CurlException\Exception as CurlException;
use webignition\GuzzleHttp\Exception\CurlException\Factory as CurlExceptionFactory;
use webignition\WebResource\WebResource;
use webignition\WebResource\Exception\Exception as WebResourceException;
//use Guzzle\Plugin\Cookie\CookiePlugin;
//use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
//use Guzzle\Plugin\Cookie\Cookie;
use GuzzleHttp\Subscriber\Cookie as HttpCookieSubscriber;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Message\Request as HttpRequest;

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

    private function init() {
        // cookies
        $cookieJar = new CookieJar();

        if ($this->task->hasParameter('cookies')) {
            foreach ($this->task->getParameter('cookies') as $cookie) {
                var_dump($cookie);
                exit();

                //$cookiePlugin->getCookieJar()->add(new Cookie($cookie));
            }


//            foreach ($this->task->getParameter('cookies') as $cookie) {
//                $cookiePlugin->getCookieJar()->add(new Cookie($cookie));
//            }
        }

        $this->getHttpClientService()->get()->getEmitter()->attach(new HttpCookieSubscriber($cookieJar));

        // auth
//            if ($this->task->hasParameter('http-auth-username') || $this->task->hasParameter('http-auth-password')) {
//                $baseRequest->setAuth(
//                    $this->task->hasParameter('http-auth-username') ? $this->task->getParameter('http-auth-username') : '',
//                    $this->task->hasParameter('http-auth-password') ? $this->task->getParameter('http-auth-password') : '',
//                    'any'
//                );
//            }


        // old

//        $cookieJar = new CookieJar();
//
//        foreach ($this->getCookies() as $cookieData) {
//            $cookieJar->setCookie(new SetCookie($cookieData));
//        }
//
//        $this->getHttpClient()->getEmitter()->attach(new HttpCookieSubscriber($cookieJar));


//            $cookiePlugin = new CookiePlugin(new ArrayCookieJar());
//            $this->getHttpClientService()->get()->addSubscriber($cookiePlugin);
//
//            $baseRequest = $this->getHttpClientService()->getRequest($this->task->getUrl());
//
//            if ($this->task->hasParameter('http-auth-username') || $this->task->hasParameter('http-auth-password')) {
//                $baseRequest->setAuth(
//                    $this->task->hasParameter('http-auth-username') ? $this->task->getParameter('http-auth-username') : '',
//                    $this->task->hasParameter('http-auth-password') ? $this->task->getParameter('http-auth-password') : '',
//                    'any'
//                );
//            }
//
//            if ($this->task->hasParameter('cookies')) {
//                foreach ($this->task->getParameter('cookies') as $cookie) {
//                    $cookiePlugin->getCookieJar()->add(new Cookie($cookie));
//                }
//            }

//        exit();
    }

    public function execute(Task $task)
    {
        $this->task = $task;

        $this->getHttpClientService()->setUserAgent('ST Web Resource Task Driver (http://bit.ly/RlhKCL)');
        $this->webResource = $this->getWebResource();
        $this->getHttpClientService()->resetUserAgent();

        if (!$this->response->hasSucceeded()) {
            return $this->hasNotSucceedHandler();
        }

        if (!$this->isCorrectWebResourceType()) {
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
    abstract protected function hasNotSucceedHandler();

    /**
     * @return boolean
     */
    abstract protected function isCorrectWebResourceType();

    abstract protected function isNotCorrectWebResourceTypeHandler();
    abstract protected function isBlankWebResourceHandler();
    abstract protected function performValidation();

//    /**
//     * @return \Guzzle\Http\Message\Request
//     */
//    protected function getBaseRequest() {
//        if (is_null($this->baseRequest)) {
//            $cookiePlugin = new CookiePlugin(new ArrayCookieJar());
//            $this->getHttpClientService()->get()->addSubscriber($cookiePlugin);
//
//            $baseRequest = $this->getHttpClientService()->getRequest($this->task->getUrl());
//
//            if ($this->task->hasParameter('http-auth-username') || $this->task->hasParameter('http-auth-password')) {
//                $baseRequest->setAuth(
//                    $this->task->hasParameter('http-auth-username') ? $this->task->getParameter('http-auth-username') : '',
//                    $this->task->hasParameter('http-auth-password') ? $this->task->getParameter('http-auth-password') : '',
//                    'any'
//                );
//            }
//
//            if ($this->task->hasParameter('cookies')) {
//                foreach ($this->task->getParameter('cookies') as $cookie) {
//                    $cookiePlugin->getCookieJar()->add(new Cookie($cookie));
//                }
//            }
//
//            $this->baseRequest = $baseRequest;
//        }
//
//        return $this->baseRequest;
//    }


    /**
     * @return HttpRequest
     */
    protected function getBaseRequest()
    {
        if (is_null($this->baseRequest)) {
            // cookies
            $cookieJar = new CookieJar();

            if ($this->task->hasParameter('cookies')) {
                foreach ($this->task->getParameter('cookies') as $cookie) {
                    var_dump($cookie);
                    exit();

                    //$cookiePlugin->getCookieJar()->add(new Cookie($cookie));
                }


//            foreach ($this->task->getParameter('cookies') as $cookie) {
//                $cookiePlugin->getCookieJar()->add(new Cookie($cookie));
//            }
            }

            $this->getHttpClientService()->get()->getEmitter()->attach(new HttpCookieSubscriber($cookieJar));

            // auth ?
//            $this->getHttpClient()->setDefaultOption(
//                'auth',
//                ['example_user', 'example_password']
//            );

            // other

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
            return $this->getWebResourceService()->get(clone $this->getBaseRequest());
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
        }

        if (!empty($this->webResourceException)) {
            return $this->webResourceException->getResponse()->getReasonPhrase();
        }

        return '';
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

        if (!empty($this->webResourceException)) {
            return $this->webResourceException->getResponse()->getStatusCode();
        }

        return '';
    }

    /**
     * @return boolean
     */
    private function isRedirectLoopException()
    {
        $history = $this->getHttpClientService()->getHistory();
        if (is_null($history)) {
            return false;
        }

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
