<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request;

class CoreApplicationCurlErrorTest extends FailureTest {

    public function setUp() {
        parent::setUp();

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            'CURL/28 Foo',
            'CURL/28 Foo',
            'CURL/28 Foo',
            'CURL/28 Foo'
        ]));
    }
}
