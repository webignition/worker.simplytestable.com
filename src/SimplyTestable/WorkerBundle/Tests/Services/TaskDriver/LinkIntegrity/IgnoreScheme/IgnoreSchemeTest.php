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
        foreach ($this->getHttpClientService()->getHistory()->getRequests(true) as $request) {
            $requestUrl = new Url($request->getUrl());
            $this->assertEquals('http', $requestUrl->getScheme());
        }
    }
}
