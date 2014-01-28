<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class ScriptUrlDiscoveryTest extends TaskDriverTest {
    
    public function testDiscoveredScriptUrlsAreRelativeToWebPageUrlNotTaskUrl() {
        $this->container->get('simplytestable.services.nodeJsLintWrapperService')->enableDeferToParentIfNoRawOutput();
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName()) . '/HttpResponses'));    
        
        $task = $this->getTask('http://avatar.ai/');
        $this->getTaskService()->perform($task);
        
        $decodedOutput = json_decode($task->getOutput()->getOutput(), true);
        
        $this->assertFalse(isset($decodedOutput['http://example.com/vendor/foo.js']));
        $this->assertTrue(isset($decodedOutput['http://sub.example.com/vendor/foo.js']));
    }
}
