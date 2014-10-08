<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request;

class CoreApplicationMaintenanceModeTest extends FailureTest {

    public function setUp() {
        parent::setUp();

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            'HTTP/1.1 503',
            'HTTP/1.1 503',
            'HTTP/1.1 503',
            'HTTP/1.1 503',
        ]));

        $this->setExpectedException(
            'SimplyTestable\WorkerBundle\Exception\Services\TasksService\RequestException',
            'ServerErrorResponseException',
            '503'
        );
    }

}
