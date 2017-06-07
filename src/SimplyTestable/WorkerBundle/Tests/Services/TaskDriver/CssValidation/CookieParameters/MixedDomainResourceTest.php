<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\CookieParameters;

class MixedDomainResourceTest extends CookieParametersTest {

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
        $requests = array();

        foreach ($this->getHttpClientService()->getHistory()->getRequests(true) as $request) {
            if ($request->getUrl() != 'http://anotherexample.com/style2.css') {
                $requests[] = $request;
            }
        }

        return $requests;
    }

    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        $requests = array();

        foreach ($this->getHttpClientService()->getHistory()->getRequests(true) as $request) {
            if ($request->getUrl() == 'http://anotherexample.com/style2.css') {
                $requests[] = $request;
            }
        }

        return $requests;
    }
}
