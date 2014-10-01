<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request;

class SuccessTest extends RequestTest {

    public function testSuccessfulRequestReturnsTrue() {
        $this->setHttpFixtures($this->buildHttpFixtureSet([
            'HTTP/1.1 200 OK'
        ]));

        $this->assertTrue($this->getService()->request());
    }

}
