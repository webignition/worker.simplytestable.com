<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request;

class CoreApplicationCurlErrorTest extends FailureTest {

    public function setUp() {
        parent::setUp();

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            'CURL/28 Foo',
            'CURL/28 Foo',
            'CURL/28 Foo',
            'CURL/28 Foo',
        ]));

        $this->setExpectedException(
            'SimplyTestable\WorkerBundle\Exception\Services\TasksService\RequestException',
            'CurlException',
            '28'
        );
    }

}
