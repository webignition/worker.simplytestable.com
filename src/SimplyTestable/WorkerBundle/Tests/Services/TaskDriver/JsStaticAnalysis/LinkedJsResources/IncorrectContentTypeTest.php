<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\LinkedJsResources;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class IncorrectContentTypeTest extends TaskDriverTest {

    public function setUp() {
        parent::setUp();

        $this->setJsLintValidatorFixture(
            file_get_contents($this->getFixturesDataPath($this->getName()) . '/NodeJslintResponse/1')
        );
    }


    /**
     * @group standard
     */
    public function testNoInvalidContentTypes() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            file_get_contents($this->getFixturesDataPath() . '/HttpResponses/1_root_resource.200.httpresponse'),
            "HTTP/1.0 200 OK\nContent-Type:application/javascript",
            "HTTP/1.0 200 OK\nContent-Type:application/javascript",
        )));

        $task = $this->getDefaultTask();
        $this->getTaskService()->perform($task);
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);

        $this->assertEquals(array(), $decodedTaskOutput['http://example.com/js/one.js']['entries']);
        $this->assertEquals(array(), $decodedTaskOutput['http://example.com/js/two.js']['entries']);
    }


    /**
     * @group standard
     */
    public function testInvalidContentTypeOneOfTwo() {
        $invalidContentType = 'foo/bar';
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            file_get_contents($this->getFixturesDataPath() . '/HttpResponses/1_root_resource.200.httpresponse'),
            "HTTP/1.0 200 OK\nContent-Type:" . $invalidContentType,
            "HTTP/1.0 200 OK\nContent-Type:application/javascript",
        )));

        $task = $this->getDefaultTask();
        $this->getTaskService()->perform($task);
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);

        $this->assertTrue(!isset($decodedTaskOutput['http://example.com/js/one.js']['entries']));
        $this->assertEquals('failed', $decodedTaskOutput['http://example.com/js/one.js']['statusLine']);
        $this->assertEquals('InvalidContentTypeException', $decodedTaskOutput['http://example.com/js/one.js']['errorReport']['reason']);
        $this->assertEquals($invalidContentType, $decodedTaskOutput['http://example.com/js/one.js']['errorReport']['contentType']);
        $this->assertEquals(array(), $decodedTaskOutput['http://example.com/js/two.js']['entries']);
    }


    /**
     * @group standard
     */
    public function testInvalidContentTypeTwoOfTwo() {
        $invalidContentType = 'foo/bar';
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            file_get_contents($this->getFixturesDataPath() . '/HttpResponses/1_root_resource.200.httpresponse'),
            "HTTP/1.0 200 OK\nContent-Type:application/javascript",
            "HTTP/1.0 200 OK\nContent-Type:" . $invalidContentType,
        )));

        $task = $this->getDefaultTask();
        $this->getTaskService()->perform($task);
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);

        $this->assertEquals(array(), $decodedTaskOutput['http://example.com/js/one.js']['entries']);
        $this->assertTrue(!isset($decodedTaskOutput['http://example.com/js/two.js']['entries']));
        $this->assertEquals('failed', $decodedTaskOutput['http://example.com/js/two.js']['statusLine']);
        $this->assertEquals('InvalidContentTypeException', $decodedTaskOutput['http://example.com/js/two.js']['errorReport']['reason']);
        $this->assertEquals($invalidContentType, $decodedTaskOutput['http://example.com/js/two.js']['errorReport']['contentType']);

    }


    /**
     * @group standard
     */
    public function testInvalidContentTypeOneAndTwoOfTwo() {
        $invalidContentType = 'foo/bar';
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            file_get_contents($this->getFixturesDataPath() . '/HttpResponses/1_root_resource.200.httpresponse'),
            "HTTP/1.0 200 OK\nContent-Type:" . $invalidContentType,
            "HTTP/1.0 200 OK\nContent-Type:" . $invalidContentType,
        )));

        $task = $this->getDefaultTask();
        $this->getTaskService()->perform($task);
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput(), true);

        $this->assertTrue(!isset($decodedTaskOutput['http://example.com/js/one.js']['entries']));
        $this->assertEquals('failed', $decodedTaskOutput['http://example.com/js/one.js']['statusLine']);
        $this->assertEquals('InvalidContentTypeException', $decodedTaskOutput['http://example.com/js/one.js']['errorReport']['reason']);
        $this->assertEquals($invalidContentType, $decodedTaskOutput['http://example.com/js/one.js']['errorReport']['contentType']);

        $this->assertTrue(!isset($decodedTaskOutput['http://example.com/js/two.js']['entries']));
        $this->assertEquals('failed', $decodedTaskOutput['http://example.com/js/two.js']['statusLine']);
        $this->assertEquals('InvalidContentTypeException', $decodedTaskOutput['http://example.com/js/two.js']['errorReport']['reason']);
        $this->assertEquals($invalidContentType, $decodedTaskOutput['http://example.com/js/two.js']['errorReport']['contentType']);
    }
}
