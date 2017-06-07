<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\LinkedJsResources\TwoResources;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

abstract class TransportErrorTest extends TaskDriverTest {

    protected $task;

    public function setUp() {
        parent::setUp();

        $this->setJsLintValidatorFixture(
            file_get_contents($this->getFixturesDataPath() . '/../NodeJslintResponse/1')
        );

        $this->task = $this->getDefaultTask();

        $this->setHttpFixtures($this->buildHttpFixtureSet($this->getTransportFixtures()));

        $this->assertEquals(0, $this->getTaskService()->perform($this->task));

        $decodedTaskOutput = json_decode($this->task->getOutput()->getOutput(), true);
        $this->assertEquals($this->getTestedStatusCode(), $decodedTaskOutput['http://example.com/js/two.js']['errorReport']['statusCode']);
    }

    /**
     *
     * @return int
     */
    protected function getTestedStatusCode() {
        return (int)  str_replace('test', '', $this->getName());
    }
}
