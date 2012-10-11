<?php

namespace SimplyTestable\WorkerBundle\Exception;
use \Exception as BaseException;

class WebResourceException extends BaseException {    
    
    /**
     *
     * @var \HttpMessage
     */
    private $response;
    
    
    /**
     *
     * @var \HttpResponse
     */
    private $request;
    
    
    /**
     * 
     * @param \HttpMessage $response
     * @param \HttpRequest $request
     */
    public function __construct(\HttpMessage $response, \HttpRequest $request = null) {
        $this->response = $response;
        $this->request = $request;
        
        parent::__construct($response->getResponseStatus(), $response->getResponseCode());
    }
    
    
    /**
     * 
     * @return \HttpMessage
     */
    public function getHttpResponse() {
        return $this->response;
    }
    
    /**
     * 
     * @return \HttpRequest
     */
    public function getHttpRequest() {
        return $this->request;
    }
    
}