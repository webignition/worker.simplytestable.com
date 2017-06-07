<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation\CookieParameters;

class SecureFalseTest extends CookieParametersTest {

    protected function getTaskUrl() {
        return 'http://example.com/';
    }


    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        return array();
    }

    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        return $this->getHttpClientService()->getHistory()->getRequests(true);
    }
}
