<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\UrlDiscovery;

class Utf8ContentInLinksTest extends TaskDriverTest {
    
    /**
     * @group standard
     */
    public function testUtf8ContentIsDecoded() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $task = $this->getTask('http://example.com/', array(
            'scope' => 'http://example.com/'
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals([
            'http://example.com/tags/foo–bar/'
        ], json_decode($task->getOutput()->getOutput()));
    }
    
}
