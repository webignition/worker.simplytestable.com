<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request;

abstract class FailureTest extends RequestTest {

    public function testSuccessfulRequestReturnsFalse() {
        $this->assertFalse($this->getService()->request());
    }

}
