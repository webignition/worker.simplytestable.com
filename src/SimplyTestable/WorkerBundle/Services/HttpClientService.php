<?php
namespace SimplyTestable\WorkerBundle\Services;

use Guzzle\Http\Client as HttpClient;
use Guzzle\Plugin\Backoff\BackoffPlugin;

class HttpClientService { 
    
    
    /**
     *
     * @var \Guzzle\Http\Client
     */
    private $httpClient = null;   
    
    
    public function get($baseUrl = '', $config = null) {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient($baseUrl, $config);
            $this->httpClient->addSubscriber(BackoffPlugin::getExponentialBackoff());            
        }
        
        return $this->httpClient;
    }
    
    
    /**
     * 
     * @param string $uri
     * @param array $headers
     * @param string $body
     * @return \Guzzle\Http\Message\Request
     */
    public function getRequest($uri = null, $headers = null, $body = null) {        
        $request = $this->get()->get($uri, $headers, $body);        
        $request->setHeader('Accept-Encoding', 'gzip,deflate');
        
        return $request;
    }
    
    
    /**
     * 
     * @param string $uri
     * @param array $headers
     * @param array $postBody
     * @return \Guzzle\Http\Message\Request
     */
    public function postRequest($uri = null, $headers = null, $postBody = null) {
        $request = $this->get()->post($uri, $headers, $postBody);        
        return $request;        
    } 
    
}