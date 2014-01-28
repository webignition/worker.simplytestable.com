<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\LinkedJsResources;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class OutputFilenamesMatchInputFilenamesTest extends TaskDriverTest {
    
    public function setUp() { 
        parent::setUp();
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            file_get_contents($this->getFixturesDataPath() . '/HttpResponses/1_root_resource.200.httpresponse'),
            "HTTP/1.0 200 OK\nContent-Type:application/javascript",
            "HTTP/1.0 200 OK\nContent-Type:application/javascript",
        )));  
        
        $this->container->get('simplytestable.services.nodeJsLintWrapperService')->setValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName()) . '/NodeJslintResponse/1')
        );         
    }    
    
    public function testOutputFilenamesMatchInputFilenames() {
        $task = $this->getDefaultTask();
        $this->getTaskService()->perform($task);
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);
        
        $this->assertTrue(isset($decodedTaskOutput['http://example.com/js/one.js']));
        $this->assertTrue(isset($decodedTaskOutput['http://example.com/js/two.js']));
    }    
}
