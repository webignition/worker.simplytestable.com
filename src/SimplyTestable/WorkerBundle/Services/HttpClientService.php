<?php
namespace SimplyTestable\WorkerBundle\Services;

use Guzzle\Http\Client as HttpClient;

class HttpClientService { 
    
    
    /**
     *
     * @var \Guzzle\Http\Client
     */
    private $httpClient = null;
    
    
//    /**
//     *
//     * @var string
//     */
//    private $userAgent = null;    
    
    
    public function get($baseUrl = '', $config = null) {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient($baseUrl = '', $config);
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
    
    
//    /**
//     * 
//     * @return boolean
//     */
//    private function hasUserAgent() {
//        return !is_null($this->userAgent);
//    }
//    
//    
//    /**
//     * 
//     * @param \Guzzle\Http\Message\Request $request
//     * @return boolean
//     */
//    private function requestHasUserAgent(\Guzzle\Http\Message\Request $request) {
//        return preg_match('/^Guzzle\//', $request->getHeader('User-Agent')) === 0;
//    }   
//
//    
//    
//    /**
//     * 
//     * @param string $userAgent
//     */
//    public function setUserAgent($userAgent = null) {
//        $this->userAgent = $userAgent;
//    }
//    
//    
//    public function clearUserAgent() {
//        $this->userAgent = null;
//    }    
    
}