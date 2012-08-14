<?php

namespace SimplyTestable\WorkerBundle\Services;

use SimplyTestable\WorkerBundle\Model\WebResource;
use SimplyTestable\WorkerBundle\Model\WebPage;

class WebResourceService {
    
    /**
     *
     * @var \webignition\Http\Client\Client
     */
    private $httpClient; 
    
    
    /**
     *
     * @param \webignition\Http\Client\Client $httpClient
     */
    public function __construct(
            \webignition\Http\Client\Client $httpClient)
    {    

        $this->httpClient = $httpClient;
    }  
    
    
    /**
     *
     * @param string $url
     * @return \SimplyTestable\WorkerBundle\Model\WebResource 
     */
    public function get($url) {
        $resource = new WebResource();
        $resource->setContentType('text/plain');
        $resource->setContent('');
        $resource->setUrl($url);
        
        return $resource;
    }
    
}