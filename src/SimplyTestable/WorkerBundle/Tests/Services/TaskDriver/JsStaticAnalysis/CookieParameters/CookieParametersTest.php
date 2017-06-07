<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\CookieParameters;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

abstract class CookieParametersTest extends TaskDriverTest {

    protected $task;

    abstract protected function getExpectedCookies();
    abstract protected function getExpectedRequestsOnWhichCookiesShouldBeSet();
    abstract protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet();

    public function setUp() {
        parent::setUp();
        $this->task = $this->getTask('http://example.com/', array(
            'cookies' => $this->getExpectedCookies()
        ));

        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath('/HttpResponses')));

        $this->setJsLintValidatorFixture(file_get_contents($this->getFixturesDataPath('../NodeJslintResponse/1')));

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


    /**
     *
     * @return array
     */
    private function getExpectedCookieValues() {
        $nameValueArray = array();

        foreach ($this->getExpectedCookies() as $cookie) {
            $nameValueArray[$cookie['name']] = $cookie['value'];
        }

        return $nameValueArray;
    }

}
