<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request;

class CoreApplicationMaintenanceModeTest extends FailureTest {

    public function setUp() {
        parent::setUp();

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            'HTTP/1.1 503',
            'HTTP/1.1 503',
            'HTTP/1.1 503',
            'HTTP/1.1 503',
        ]));
    }
}
