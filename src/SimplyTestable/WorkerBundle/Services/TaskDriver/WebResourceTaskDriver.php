<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebResource;

abstract class WebResourceTaskDriver extends TaskDriver {        
    
    /**
     *
     * @var \webignition\Http\Client\Exception
     */
    protected $httpClientException;
    
    
    /**
     *
     * @param Task $task
     * @return WebResource 
     */
    protected function getWebResource(Task $task) {
        try {
            $resourceRequest = new \HttpRequest($task->getUrl(), HTTP_METH_GET);
            return $this->getWebResourceService()->get($resourceRequest);
        } catch (\webignition\Http\Client\Exception $httpClientException) {
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
            
            $this->httpClientException = $httpClientException;
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
        if (!$this->httpClientException instanceof \webignition\Http\Client\Exception) {
            return '';
        }
        
        switch ($this->httpClientException->getCode()) {
            case 310:
                return 'Redirect limit of ' . $this->getWebResourceService()->getHttpClient()->redirectHandler()->limit().' redirects reached';                
            
            case 311:
                return 'Redirect loop deteted';
                break;
        }
    }

    
    /**
     *
     * @return string 
     */
    private function getOutputMessageId() {
        if (!$this->httpClientException instanceof \webignition\Http\Client\Exception) {
            return '';
        }
        
        switch ($this->httpClientException->getCode()) {
            case 310:
                return 'redirect-limit-reached';                
            
            case 311:
                return 'redirect-loop';
                break;
        }
    }   
    
}