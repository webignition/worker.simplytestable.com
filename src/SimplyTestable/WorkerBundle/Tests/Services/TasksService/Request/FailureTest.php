<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request;

abstract class FailureTest extends RequestTest {

    public function setUp() {
        parent::setUp();
        $this->getHttpClientService()->getMockPlugin()->clearQueue();
    }

    public function testSuccessfulRequestReturnsFalse() {
        $this->assertFalse($this->getService()->request());
    }

}
