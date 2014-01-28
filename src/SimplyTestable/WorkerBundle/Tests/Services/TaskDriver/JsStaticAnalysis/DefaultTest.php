<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class DefaultTest extends TaskDriverTest {
    
    public function setUp() {
        parent::setUp();
        
        $this->container->get('simplytestable.services.nodeJsLintWrapperService')->setValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/NodeJslintResponse/1'))
        );         
    }
    
    public function testEvidenceIsTruncated() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName()) . '/HttpResponses'));    

        $task = $this->getDefaultTask();
        $this->getTaskService()->perform($task);
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);
        
        $this->assertEquals(256, strlen($decodedTaskOutput['http://example.com/js/app.js']['entries'][0]['fragmentLine']['fragment']));
    }
    
    
    public function testIncorrectPathToNodeJslint() {
        $this->setExpectedException('webignition\NodeJslintOutput\Exception', 'node-jslint not found at "/home/example/node_modules/jslint/bin/jslint.js"', 3);
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName()) . '/HttpResponses'));    
        
        $task = $this->getDefaultTask();
        $this->getTaskService()->perform($task);
    }
}
