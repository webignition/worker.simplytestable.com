<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation\CookieParameters;

use GuzzleHttp\Message\RequestInterface;
use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation\TaskDriverTest;

abstract class CookieParametersTest extends TaskDriverTest {

    protected $task;

    private $cookies = array(
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

    abstract protected function getTaskUrl();

    /**
     * @return RequestInterface[]
     */
    abstract protected function getExpectedRequestsOnWhichCookiesShouldBeSet();

    /**
     * @return RequestInterface[]
     */
    abstract protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet();

    public function setUp() {
        parent::setUp();
        $this->task = $this->getTask($this->getTaskUrl(), array(
            'cookies' => $this->cookies
        ));

        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 200 OK'
        )));

        $this->getTaskService()->perform($this->task);
    }

    public function testCookiesAreSetOnExpectedRequests() {
        $expectedCookieKeyValueStrings = [];

        foreach ($this->getExpectedCookieValues() as $key => $value) {
            $expectedCookieKeyValueStrings[] = $key . '=' . $value;
        }

        $expectedCookieHeader = implode('; ', $expectedCookieKeyValueStrings);

        foreach ($this->getExpectedRequestsOnWhichCookiesShouldBeSet() as $request) {
            $this->assertEquals($expectedCookieHeader, $request->getHeader('cookie'));
        }
    }

    public function testCookiesAreNotSetOnExpectedRequests() {
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldNotBeSet() as $request) {
            $this->assertEmpty($request->getHeader('cookie'));
        }
    }

    public function testCurlOptionsAreSetOnAllRequests() {
        $this->assertSystemCurlOptionsAreSetOnAllRequests();
    }


    /**
     *
     * @return array
     */
    private function getExpectedCookieValues() {
        $nameValueArray = array();

        foreach ($this->cookies as $cookie) {
            $nameValueArray[$cookie['name']] = $cookie['value'];
        }

        return $nameValueArray;
    }

}
