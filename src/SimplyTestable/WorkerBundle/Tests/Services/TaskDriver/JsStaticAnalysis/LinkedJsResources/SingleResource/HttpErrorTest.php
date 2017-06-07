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
            'HTTP/1.0 ' . $this->getTestedStatusCode(),
            'HTTP/1.0 ' . $this->getTestedStatusCode(),
            'HTTP/1.0 ' . $this->getTestedStatusCode(),
            'HTTP/1.0 ' . $this->getTestedStatusCode(),
            'HTTP/1.0 ' . $this->getTestedStatusCode(),
            'HTTP/1.0 ' . $this->getTestedStatusCode(),
            'HTTP/1.0 ' . $this->getTestedStatusCode(),
            'HTTP/1.0 ' . $this->getTestedStatusCode(),
            'HTTP/1.0 ' . $this->getTestedStatusCode(),
            'HTTP/1.0 ' . $this->getTestedStatusCode(),
        )));

        $task = $this->getDefaultTask();

        $this->assertEquals(0, $this->getTaskService()->perform($task));

        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);
        $this->assertEquals($this->getTestedStatusCode(), $decodedTaskOutput['http://example.com/js/one.js']['errorReport']['statusCode']);
    }


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


    /**
     *
     * @return int
     */
    private function getTestedStatusCode() {
        return (int)  str_replace('test', '', $this->getName());
    }
}
