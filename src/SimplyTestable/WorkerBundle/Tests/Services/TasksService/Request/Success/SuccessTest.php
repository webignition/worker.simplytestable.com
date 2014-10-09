<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request\Success;

use SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request\RequestTest;

abstract class SuccessTest extends RequestTest {

    private $returnValue;

    public function setUp() {
        parent::setUp();

        $this->removeAllTasks();

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            'HTTP/1.1 200 OK'
        ]));

        $this->returnValue = $this->getService()->request($this->getTaskRequestLimit());
    }

    protected function getTaskRequestLimit() {
        return null;
    }


    abstract protected function getExpectedRequestedTaskCount();


    public function testSuccessfulRequestReturnsTrue() {
        $this->assertTrue($this->returnValue);
    }


    public function testRequestedTaskCount() {
        $this->assertEquals(
            $this->getExpectedRequestedTaskCount(),
            $this->getHttpClientService()->getHistory()->getLastRequest()->getPostFields()->get('limit')
        );
    }

}
