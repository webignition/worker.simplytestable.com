<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\CookieParameters;

class ZeroResourcesTest extends CookieParametersTest {

    protected function getExpectedCookies() {
        return array(
            array(
                'domain' => '.example.com',
                'name' => 'key1',
                'value' => 'value1'
            ),
            array(
                'domain' => '.example.com',
                'name' => 'key2',
                'value' => 'value2'
            )
        );
    }

    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        return $this->getHttpClientService()->getHistory()->getRequests(true);
    }

    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        return array();
    }

}
