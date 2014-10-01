<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request;

class CoreApplicationServerErrorTest extends FailureTest {

    public function setUp() {
        parent::setUp();

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            'HTTP/1.1 500',
            'HTTP/1.1 500',
            'HTTP/1.1 500',
            'HTTP/1.1 500',
        ]));
    }

}
