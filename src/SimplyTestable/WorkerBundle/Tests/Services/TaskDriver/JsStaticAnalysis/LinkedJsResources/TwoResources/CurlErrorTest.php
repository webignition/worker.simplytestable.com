<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\LinkedJsResources\TwoResources;

class CurlErrorTest extends TransportErrorTest {

    protected function getTransportFixtures() {
        return array(
            file_get_contents($this->getFixturesDataPath() . '/../HttpResponses/1_root_resource.200.httpresponse'),
            "HTTP/1.0 200 OK\nContent-Type:application/javascript",
            'CURL/' . $this->getTestedStatusCode() . ' foo',
        );
    }

    /**
     * @group standard
     */
    public function test6() {}

    /**
     * @group standard
     */
    public function test28() {}

    /**
     * @group standard
     */
    public function test55() {}
}
