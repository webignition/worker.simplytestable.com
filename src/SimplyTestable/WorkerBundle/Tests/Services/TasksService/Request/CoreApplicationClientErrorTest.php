<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request;

class CoreApplicationClientErrorTest extends FailureTest {

    public function setUp() {
        parent::setUp();

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            'HTTP/1.1 400'
        ]));
    }

}
