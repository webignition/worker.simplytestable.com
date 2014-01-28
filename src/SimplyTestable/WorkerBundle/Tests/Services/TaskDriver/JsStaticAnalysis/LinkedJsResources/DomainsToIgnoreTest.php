<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\LinkedJsResources;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class DomainsToIgnoreTest extends TaskDriverTest {
    
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
    
    public function testIgnoreNoDomains() {
        $task = $this->getDefaultTask();
        $this->getTaskService()->perform($task);
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);
        
        $this->assertEquals(2, count($decodedTaskOutput));
    }
    
    public function testIgnoreDomainsOneOfTwo() {        
        $task = $this->getTask('http://example.com/', array(
            'domains-to-ignore' => array(
                'one.example.com'
            )
        ));
        
        $this->getTaskService()->perform($task);
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);
        
        $this->assertEquals(1, count($decodedTaskOutput));        
        $this->assertTrue(isset($decodedTaskOutput['http://two.example.com/js/one.js']));
    }    
    
    public function testIgnoreDomainsTwoOfTwo() {        
        $task = $this->getTask('http://example.com/', array(
            'domains-to-ignore' => array(
                'two.example.com'
            )
        ));
        
        $this->getTaskService()->perform($task);
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);
        
        $this->assertEquals(1, count($decodedTaskOutput));     
        $this->assertTrue(isset($decodedTaskOutput['http://one.example.com/js/one.js']));        
    }    
    
    public function testIgnoreDomainsOneAndTwoOfTwo() {        
        $task = $this->getTask('http://example.com/', array(
            'domains-to-ignore' => array(
                'one.example.com',
                'two.example.com'
            )
        ));
        
        $this->getTaskService()->perform($task);
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);
        
        $this->assertEquals(0, count($decodedTaskOutput));
    }     
}
