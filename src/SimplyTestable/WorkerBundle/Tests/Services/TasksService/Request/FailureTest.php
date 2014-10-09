<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request;

abstract class FailureTest extends RequestTest {

    public function setUp() {
        parent::setUp();

        $this->removeAllTasks();

        $this->getHttpClientService()->getMockPlugin()->clearQueue();
    }

    public function testExceptionIsThrow() {
        $this->getService()->request();
        $this->fail('RequestException not thrown');
    }

}
