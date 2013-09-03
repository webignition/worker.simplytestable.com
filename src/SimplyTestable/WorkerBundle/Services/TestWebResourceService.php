<?php

namespace SimplyTestable\WorkerBundle\Services;

class TestWebResourceService extends WebResourceService { 
    
    private $requestSkeletonToCurlErrorMap = array();
    
    public function setRequestSkeletonToCurlErrorMap($requestSkeletonToCurlErrorMap) {
        $this->requestSkeletonToCurlErrorMap = $requestSkeletonToCurlErrorMap;
    }
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @return \Guzzle\Http\Message\Response
     * @throws \Guzzle\Http\Exception\CurlException
     */
    public function get(\Guzzle\Http\Message\Request $request) {
        if (!is_null($curlErrorDetails = $this->getCurlErrorForRequest($request))) {
            $curlException = new \Guzzle\Http\Exception\CurlException();
            $curlException->setError(
                $curlErrorDetails['errorMessage'],
                $curlErrorDetails['errorNumber']
            );       

            throw $curlException;                      
        }
        
        return parent::get($request);
    }
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @return array
     */
    private function getCurlErrorForRequest(\Guzzle\Http\Message\Request $request) {
        if (!isset($this->requestSkeletonToCurlErrorMap[$request->getUrl()])) {
            return null;
        }
        
        if (!isset($this->requestSkeletonToCurlErrorMap[$request->getUrl()][$request->getMethod()])) {
            return null;
        } 
        
        return $this->requestSkeletonToCurlErrorMap[$request->getUrl()][$request->getMethod()];
    }
    
}