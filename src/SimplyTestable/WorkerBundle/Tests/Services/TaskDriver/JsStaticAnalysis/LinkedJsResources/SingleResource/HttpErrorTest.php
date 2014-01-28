<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\LinkedJsResources\SingleResource;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class HttpErrorTest extends TaskDriverTest {
    
    public function setUp() { 
        parent::setUp();
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            file_get_contents($this->getFixturesDataPath() . '/../HttpResponses/1_root_resource.200.httpresponse'),
            'HTTP/1.0 ' . $this->getTestedStatusCode(),
            'HTTP/1.0 ' . $this->getTestedStatusCode(), // Web resource service retries in case of incorrectly-encoded URL
            'HTTP/1.0 ' . $this->getTestedStatusCode(), // Http client retries on HTTP server error (1)
            'HTTP/1.0 ' . $this->getTestedStatusCode(), // Http client retries on HTTP server error (2)
            'HTTP/1.0 ' . $this->getTestedStatusCode()  // Http client retries on HTTP server error (3)
        )));
        
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);
        $this->assertEquals($this->getTestedStatusCode(), $decodedTaskOutput['http://example.com/js/one.js']['errorReport']['statusCode']);
    }    
    
    public function test401() {}
    public function test404() {}
    public function test500() {}
    public function test503() {}
    
    
    /**
     * 
     * @return int
     */
    private function getTestedStatusCode() {
        return (int)  str_replace('test', '', $this->getName());
    }
}
