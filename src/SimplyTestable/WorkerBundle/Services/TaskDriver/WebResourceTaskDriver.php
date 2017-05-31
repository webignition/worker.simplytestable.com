<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebResource;
use webignition\WebResource\Exception\Exception as WebResourceException;
//use Guzzle\Plugin\Cookie\CookiePlugin;
//use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
//use Guzzle\Plugin\Cookie\Cookie;
use GuzzleHttp\Subscriber\Cookie as HttpCookieSubscriber;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Message\Request as HttpRequest;

abstract class WebResourceTaskDriver extends TaskDriver {

    /**
     *
     * @var WebResourceException
     */
    protected $webResourceException;

    /**
     *
     * @var \webignition\Http\Client\CurlException
     */
    protected $curlException;


    /**
     *
     * @var \webignition\WebResource\WebResource
     */
    protected $webResource;


    /**
     *
     * @var \SimplyTestable\WorkerBundle\Entity\Task\Task
     */
    protected $task;


    /**
     *
     * @var \Guzzle\Http\Exception\TooManyRedirectsException
     */
    private $tooManyRedirectsException = null;


    /**
     *
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


    public function execute(Task $task) {
        if (!$this->isCorrectTaskType($task)) {
            return false;
        }

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

        if ($this->webResource->getContent() == '') {
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


    /**
     * @return mixed
     */
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
    protected function getBaseRequest() {
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
     *
     * @return WebResource
     */
    protected function getWebResource() {
        try {
//            $this->init();
//
//            $request = $this->getHttpClientService()->get()->createRequest(
//                'GET',
//                $this->task->getUrl()
//            );

            $request = clone $this->getBaseRequest();
            return $this->getWebResourceService()->get($request);
        } catch (WebResourceException $webResourceException) {
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);

            $this->webResourceException = $webResourceException;
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {
            $this->response->setHasFailed();

            if ($this->isTimeoutException($curlException)) {
                $this->response->setIsRetryable(false);
            }

            if ($this->isDnsLookupFailureException($curlException)) {
                $this->response->setIsRetryable(false);
            }

            if ($this->isInvalidUrlException($curlException)) {
                $this->response->setIsRetryable(false);
            }

            $this->curlException = $curlException;
        } catch (\Guzzle\Http\Exception\TooManyRedirectsException $tooManyRedirectsException) {
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);

            $this->tooManyRedirectsException = $tooManyRedirectsException;
        }
    }


    /**
     *
     * @return \stdClass
     */
    protected function getWebResourceExceptionOutput() {
        $outputObjectMessage = new \stdClass();
        $outputObjectMessage->message = $this->getOutputMessage();
        $outputObjectMessage->messageId = 'http-retrieval-' . $this->getOutputMessageId();
        $outputObjectMessage->type = 'error';

        $outputObject = new \stdClass();
        $outputObject->messages = array($outputObjectMessage);

        return $outputObject;
    }


    /**
     *
     * @return string
     */
    private function getOutputMessage() {
        if ($this->tooManyRedirectsException instanceof \Guzzle\Http\Exception\TooManyRedirectsException) {
            if ($this->isRedirectLoopException()) {
                return 'Redirect loop detected';
            }

            return 'Redirect limit reached';
        }

        if ($this->curlException instanceof \Guzzle\Http\Exception\CurlException) {
            if ($this->isTimeoutException($this->curlException)) {
                return 'Timeout reached retrieving resource';
            }

            if ($this->isDnsLookupFailureException($this->curlException)) {
                return 'DNS lookup failure resolving resource domain name';
            }

            if ($this->isInvalidUrlException($this->curlException)) {
                return 'Invalid resource URL';
            }
        }

        if ($this->webResourceException instanceof WebResourceException) {
            return $this->webResourceException->getResponse()->getReasonPhrase();
        }

        return '';
    }


    /**
     *
     * @return boolean
     */
    private function isRedirectLoopException() {
        $history = $this->getHttpClientService()->getHistory();
        if (is_null($history)) {
            return false;
        }

        $urlHistory = array();

        foreach ($history->getAll() as $transaction) {
            $urlHistory[] = $transaction['request']->getUrl();
        }

        foreach ($urlHistory as $urlIndex => $url) {
            if (in_array($url, array_slice($urlHistory, $urlIndex + 1))) {
                return true;
            }
        }

        return false;
    }


    /**
     *
     * @return string
     */
    private function getOutputMessageId() {
        if ($this->tooManyRedirectsException instanceof \Guzzle\Http\Exception\TooManyRedirectsException) {
            if ($this->isRedirectLoopException()) {
                return 'redirect-loop';
            }

            return 'redirect-limit-reached';
        }

        if ($this->curlException instanceof \Guzzle\Http\Exception\CurlException) {
            return 'curl-code-' . $this->curlException->getErrorNo();
        }

        if ($this->webResourceException instanceof WebResourceException) {
            return $this->webResourceException->getResponse()->getStatusCode();
        }

        return '';
    }


    /**
     *
     * @param \Guzzle\Http\Exception\CurlException $curlException
     * @return boolean
     */
    public function isInvalidUrlException(\Guzzle\Http\Exception\CurlException $curlException) {
        return $curlException->getErrorNo() === 3;
    }


    /**
     *
     * @param \Guzzle\Http\Exception\CurlException $curlException
     */
    public function isTimeoutException(\Guzzle\Http\Exception\CurlException $curlException) {
        return $curlException->getErrorNo() === 28;
    }


    /**
     *
     * @param \Guzzle\Http\Exception\CurlException $curlException
     */
    public function isDnsLookupFailureException(\Guzzle\Http\Exception\CurlException $curlException) {
        return $curlException->getErrorNo() === 6;
    }

}