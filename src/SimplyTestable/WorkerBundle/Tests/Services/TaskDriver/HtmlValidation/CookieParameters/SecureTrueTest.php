<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation\CookieParameters;

class SecureTrueTest extends CookieParametersTest {

    protected function getTaskUrl() {
        return 'https://example.com/';
    }


    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        return $this->getHttpClientService()->getHistory()->getRequests(true);
    }

    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        return array();
    }
}
