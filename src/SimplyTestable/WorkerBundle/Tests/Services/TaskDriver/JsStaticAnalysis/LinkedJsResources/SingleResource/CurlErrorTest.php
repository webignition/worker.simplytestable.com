<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\LinkedJsResources\SingleResource;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class CurlErrorTest extends TaskDriverTest {
    
    public function setUp() { 
        parent::setUp();
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            file_get_contents($this->getFixturesDataPath() . '/../HttpResponses/1_root_resource.200.httpresponse'),
            'CURL/' . $this->getTestedStatusCode() . ' message',
            'CURL/' . $this->getTestedStatusCode() . ' message', // Http client retries on HTTP transport error (1)
            'CURL/' . $this->getTestedStatusCode() . ' message', // Http client retries on HTTP transport error (2)
            'CURL/' . $this->getTestedStatusCode() . ' message', // Http client retries on HTTP transport error (3)
        )));
        
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);
        $this->assertEquals($this->getTestedStatusCode(), $decodedTaskOutput['http://example.com/js/one.js']['errorReport']['statusCode']);
    }    
    
    public function test6() {}
    public function test28() {}
    public function test55() {}
    
    
    /**
     * 
     * @return int
     */
    private function getTestedStatusCode() {
        return (int)  str_replace('test', '', $this->getName());
    }
}
