<?php
namespace SimplyTestable\WorkerBundle\Services;

use Guzzle\Http\Client as HttpClient;

class TestHttpClientService extends HttpClientService {   
    
    public function get($baseUrl = '', $config = null) {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient($baseUrl, $config);        
        }
        
        return $this->httpClient;
    }
    
}