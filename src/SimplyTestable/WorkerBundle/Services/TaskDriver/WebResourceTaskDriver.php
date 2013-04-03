<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebResource;
use SimplyTestable\WorkerBundle\Exception\WebResourceException;

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
     * @var boolean
     */
    protected $canCacheValidationOutput = false;
    
    
    /**
     *
     * @var string
     */
    private $webResourceTaskHash = null;
    
    
    /**
     *
     * @var \Guzzle\Http\Exception\TooManyRedirectsException
     */
    private $tooManyRedirectsException = null;
    
    
    public function execute(Task $task) {                
        if (!$this->isCorrectTaskType($task)) {
            return false;
        }
        
        $this->task = $task;
                
        /* @var $webResource WebPage */
        $this->getWebResourceService()->getHttpClientService()->get()->setUserAgent('SimplyTestable Web Resource Task Driver/0.1 (http://simplytestable.com/)');
        $this->webResource = $this->getWebResource($task);        
        $this->getWebResourceService()->getHttpClientService()->get()->setUserAgent(null);        

        if (!$this->response->hasSucceeded()) {            
            return $this->hasNotSucceedHandler();
        }
        
        if (!$this->isCorrectWebResourceType()) {
            return $this->isNotCorrectWebResourceTypeHandler();
        }
        
        if ($this->webResource->getContent() == '') {
            return $this->isBlankWebResourceHandler();
        }
        
//        if ($this->canCacheValidationOutput()) {            
//            $hash = $this->getWebResourceTaskHash();                
//            if ($this->getWebResourceTaskOutputService()->has($hash)) {
//                $webResourceTaskOutput = $this->getWebResourceTaskOutputService()->find($hash);
//                $this->response->setErrorCount($webResourceTaskOutput->getErrorCount());
//                return $webResourceTaskOutput->getOutput();
//            }            
//        }
        
        $validationOutput = $this->performValidation();
        
//        if ($this->canCacheValidationOutput()) {
//            $hash = $this->getWebResourceTaskHash();
//            $this->getWebResourceTaskOutputService()->create($hash, $validationOutput, $this->response->getErrorCount());
//        }
        
        return $validationOutput;
    }
    
    
    /**
     * 
     * @return string
     */
    protected function getWebResourceTaskHash() {
        if (is_null($this->webResourceTaskHash)) {
            if (!is_null($this->webResource) && !is_null($this->task)) {
                $this->webResourceTaskHash = md5($this->webResource->getContent() . $this->task->getType()->getName());
            }
        }
        
        return $this->webResourceTaskHash;
    }
    
    
    /**
     * @return boolean
     */
    protected function canCacheValidationOutput() {
        return $this->canCacheValidationOutput;
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
    
    /**
     *
     * @param Task $task
     * @return WebResource 
     */
    protected function getWebResource(Task $task) {
        try {
            $request = $this->getWebResourceService()->getHttpClientService()->getRequest($task->getUrl());
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
        // Still need to catch redirect limits and redirect loops        
        if ($this->tooManyRedirectsException instanceof \Guzzle\Http\Exception\TooManyRedirectsException) {            
            return 'Redirect limit of 4 redirects reached';
        }
//        if ($this->httpClientException instanceof \webignition\Http\Client\Exception) {
//            switch ($this->httpClientException->getCode()) {
//                case 310:
//                    return 'Redirect limit of ' . $this->getWebResourceService()->getHttpClient()->redirectHandler()->limit().' redirects reached';                
//
//                case 311:
//                    return 'Redirect loop deteted';
//                    break;
//            }
//        }
        
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
            return $this->webResourceException->getResponse()->getStatusCode();
        }
        
        return '';
    }

    
    /**
     *
     * @return string 
     */
    private function getOutputMessageId() {        
        if ($this->tooManyRedirectsException instanceof \Guzzle\Http\Exception\TooManyRedirectsException) {
            return 'redirect-limit-reached';
            
            var_dump($this->tooManyRedirectsException->getMessage());
        }
        //exit();
        
//        if ($this->httpClientException instanceof \webignition\Http\Client\Exception) {
//            switch ($this->httpClientException->getCode()) {
//                case 310:
//                    return 'redirect-limit-reached';                
//
//                case 311:
//                    return 'redirect-loop';
//                    break;
//            }
//        }
        
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