<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\CookieParameters;

class SecureResourceTest extends CookieParametersTest {

    protected function getExpectedCookies() {
        return array(
            array(
                'domain' => '.example.com',
                'secure' => true,
                'name' => 'key1',
                'value' => 'value1'
            ),
            array(
                'domain' => '.example.com',
                'secure' => true,
                'name' => 'key2',
                'value' => 'value2'
            )
        );
    }

    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        return $this->getHttpClientService()->getHistory()->getRequests(true)[0];
    }

    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        return $this->getHttpClientService()->getHistory()->getLastRequest(true);
    }
}
