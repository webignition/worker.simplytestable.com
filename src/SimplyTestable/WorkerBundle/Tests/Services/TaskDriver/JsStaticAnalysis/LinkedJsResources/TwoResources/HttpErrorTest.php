<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\LinkedJsResources\TwoResources;

class HttpErrorTest extends TransportErrorTest {   
    
    /**
     * @group standard
     */      
    public function test401() {}
    
    /**
     * @group standard
     */      
    public function test404() {}
    
    /**
     * @group standard
     */      
    public function test500() {}
    
    /**
     * @group standard
     */      
    public function test503() {}    
    
    protected function getTransportFixtures() {
        return array(
            file_get_contents($this->getFixturesDataPath() . '/../HttpResponses/1_root_resource.200.httpresponse'),
            "HTTP/1.0 200 OK\nContent-Type:application/javascript",
            'HTTP/1.0 ' . $this->getTestedStatusCode(), 
            'HTTP/1.0 ' . $this->getTestedStatusCode(), // Web resource service retries in case of incorrectly-encoded URL
            'HTTP/1.0 ' . $this->getTestedStatusCode(), // Http client retries on HTTP server error (1)
            'HTTP/1.0 ' . $this->getTestedStatusCode(), // Http client retries on HTTP server error (2)
            'HTTP/1.0 ' . $this->getTestedStatusCode()  // Http client retries on HTTP server error (3)
        );
    }
    
    

}
