<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity\IgnoreScheme;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity\TaskDriverTest;
use webignition\Url\Url;

abstract class IgnoreSchemeTest extends TaskDriverTest {

    protected function getTaskParameters() {
        return [];
    }

    protected function getExpectedErrorCount() {
        return 0;
    }

    public function testAllRequestAreForHttpResources() {
        foreach ($this->getAllRequests() as $request) {
            $requestUrl = new Url($request->getUrl());
            $this->assertEquals('http', $requestUrl->getScheme());
        }
    }

    /**
     * @return \Guzzle\Http\Message\Request[]
     */
    private function getAllRequests() {
        $requests = array();

        foreach ($this->getHttpClientService()->getHistory()->getAll() as $httpTransaction) {
            $requests[] = $httpTransaction['request'];
        }

        return $requests;
    }
    
}
