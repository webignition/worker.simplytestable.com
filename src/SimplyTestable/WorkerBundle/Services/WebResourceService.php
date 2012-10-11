<?php

namespace SimplyTestable\WorkerBundle\Services;

use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;
use SimplyTestable\WorkerBundle\Exception\WebResourceException;

class WebResourceService {
    
    const RESPONSE_CLASS_INFORMATIONAL = 1;
    const RESPONSE_CLASS_SUCCESS = 2;
    const RESPONSE_CLASS_REDIRECTION = 3;
    const RESPONSE_CLASS_CLIENT_ERROR = 4;
    const RESPONSE_CLASS_SERVER_ERROR = 5;    
    
    /**
     *
     * @var \webignition\Http\Client\Client
     */
    private $httpClient; 
    
    
    /**
     * Maps content types to WebResource subclasses
     * 
     * @var array
     */
    private $contentTypeWebResourceMap = array();
    
    
    /**
     *
     * @param \webignition\Http\Client\Client $httpClient
     * @param array $contentTypeWebResourceMap
     */
    public function __construct(
            \webignition\Http\Client\Client $httpClient,
            $contentTypeWebResourceMap)
    {
        $this->httpClient = $httpClient;
        $this->httpClient->redirectHandler()->enable();        
        $this->httpClient->redirectHandler()->setLimit(10);      
        
        $this->contentTypeWebResourceMap = $contentTypeWebResourceMap;        
    }
    
    
    /**
     *
     * @param \HttpRequest $request
     * @return \webignition\WebResource\WebResource 
     */
    public function get($request) {
        $response = $this->httpClient->getResponse($request);
        
        switch ($this->getResponseClass($response)) {
            case self::RESPONSE_CLASS_INFORMATIONAL:
                // Interesting to see what makes this happen
                break;
            
            case self::RESPONSE_CLASS_SUCCESS:
                $mediaTypeParser = new InternetMediaTypeParser();
                $contentType = $mediaTypeParser->parse($response->getHeader('content-type'));

                $webResourceClassName = $this->getWebResourceClassName($contentType->getTypeSubtypeString());

                $resource = new $webResourceClassName;
                $resource->setContent($response->getBody());
                $resource->setContentType($response->getHeader('content-type'));
                $resource->setUrl($request->getUrl());       

                return $resource;
            
            case self::RESPONSE_CLASS_REDIRECTION:
                // Shouldn't happen, HTTP client should have the redirect handler
                // enabled, redirects should be followed
                break;
            
            case self::RESPONSE_CLASS_CLIENT_ERROR:          
            case self::RESPONSE_CLASS_SERVER_ERROR:
                // Both client and server errors intentionally handled together
                throw new WebResourceException($response, $request);                
                break;
        }
    }
    
    
    /**
     * 
     * @param \HttpMessage $response
     * @return int
     */
    private function getResponseClass(\HttpMessage $response) {
        return (int)substr($response->getResponseCode(), 0, 1);
    }
    
    
    /**
     *
     * @return \webignition\Http\Client\Client
     */
    public function getHttpClient() {
        return $this->httpClient;
    }
    

    /**
     * Get the WebResource subclass name for a given content type
     * 
     * @param string $contentType
     * @return string
     */
    private function getWebResourceClassName($contentType) {
        return (isset($this->contentTypeWebResourceMap[$contentType])) ? $this->contentTypeWebResourceMap[$contentType] : $this->contentTypeWebResourceMap['default'];
    }
    
}