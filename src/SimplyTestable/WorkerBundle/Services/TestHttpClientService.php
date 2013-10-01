<?php
namespace SimplyTestable\WorkerBundle\Services;

class TestHttpClientService extends HttpClientService {   
    
    protected function getPlugins() {        
        return array(
            $this->getCachePlugin(),
            new \Guzzle\Plugin\History\HistoryPlugin()
        );
    }
    
}