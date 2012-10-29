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
     * @var \webignition\Http\Client\Exception
     */
    protected $httpClientException;
    
    /**
     *
     * @var \webignition\Http\Client\CurlException  
     */
    protected $curlException;
    
    
    /**
     *
     * @var webignition\WebResource\WebResource 
     */
    protected $webResource;
    
    
    /**
     *
     * @var boolean
     */
    protected $canCacheValidationOutput = false;
    
    
    public function execute(Task $task) {        
        if (!$this->isCorrectTaskType($task)) {
            return false;
        }             
                
        /* @var $webResource WebPage */
        $this->getWebResourceService()->getHttpClient()->setUserAgent('SimplyTestable HTML Validator/0.1 (http://simplytestable.com/)');
        $this->webResource = $this->getWebResource($task);
        $this->getWebResourceService()->getHttpClient()->clearUserAgent();

        if (!$this->response->hasSucceeded()) {
            return $this->hasNotSucceedHandler();
        }
        
        if (!$this->isCorrectWebResourceType()) {
            return $this->isNotCorrectWebResourceTypeHandler();
        }
        
        if ($this->webResource->getContent() == '') {
            return $this->isBlankWebResourceHandler();
        }
        
        $hash = md5($this->webResource->getContent());                
        if ($this->getWebResourceTaskOutputService()->has($hash)) {
            $webResourceTaskOutput = $this->getWebResourceTaskOutputService()->find($hash);
            $this->response->setErrorCount($webResourceTaskOutput->getErrorCount());
            return $webResourceTaskOutput->getOutput();
        }
        
        $validationOutput = $this->performValidation();
        
        if ($this->canCacheValidationOutput()) {
            $this->getWebResourceTaskOutputService()->create($hash, $validationOutput, $this->response->getErrorCount());
        }
        
        return $validationOutput;
    }
    
    
    /**
     * @return boolean
     */
    protected function canCacheValidationOutput() {
        return $this->canCacheValidationOutput;
    }
    
    
    /**
     * @return boolean
     */
    abstract protected function isCorrectTaskType(Task $task);

    
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
            $resourceRequest = new \HttpRequest($task->getUrl(), HTTP_METH_GET);
            return $this->getWebResourceService()->get($resourceRequest);            
        } catch (WebResourceException $webResourceException) {
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
            
            $this->webResourceException = $webResourceException;           
        } catch (\webignition\Http\Client\Exception $httpClientException) {
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
            
            $this->httpClientException = $httpClientException;
        } catch (\webignition\Http\Client\CurlException $curlException) {
            $this->response->setHasFailed();
            
            if ($curlException->isTimeoutException()) {
                $this->response->setIsRetryable(false);
            }
            
            if ($curlException->isDnsLookupFailureException()) {
                $this->response->setIsRetryable(false);
            }            
            
            if ($curlException->isInvalidUrlException()) {
                $this->response->setIsRetryable(false);
            }              
            
            $this->curlException = $curlException;
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
        if ($this->httpClientException instanceof \webignition\Http\Client\Exception) {
            switch ($this->httpClientException->getCode()) {
                case 310:
                    return 'Redirect limit of ' . $this->getWebResourceService()->getHttpClient()->redirectHandler()->limit().' redirects reached';                

                case 311:
                    return 'Redirect loop deteted';
                    break;
            }
        }
        
        if ($this->curlException instanceof \webignition\Http\Client\CurlException) {
            if ($this->curlException->isTimeoutException()) {
                return 'Timeout reached retrieving resource';
            }
            
            if ($this->curlException->isDnsLookupFailureException()) {
                return 'DNS lookup failure resolving resource domain name';
            }            
            
            if ($this->curlException->isInvalidUrlException()) {
                return 'Invalid resource URL';
            }                        
        }
        
        if ($this->webResourceException instanceof WebResourceException) {
            return $this->webResourceException->getHttpResponse()->getResponseStatus();
        }
        
        return '';
    }

    
    /**
     *
     * @return string 
     */
    private function getOutputMessageId() {        
        if ($this->httpClientException instanceof \webignition\Http\Client\Exception) {
            switch ($this->httpClientException->getCode()) {
                case 310:
                    return 'redirect-limit-reached';                

                case 311:
                    return 'redirect-loop';
                    break;
            }
        }
        
        if ($this->curlException instanceof \webignition\Http\Client\CurlException) {
            return 'curl-code-' . $this->curlException->getCode();                     
        }        
        
        if ($this->webResourceException instanceof WebResourceException) {
            return $this->webResourceException->getHttpResponse()->getResponseCode();
        }        
        
        return '';        
    }
    
}