<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation\TaskDriverTest;

class CookieParametersTest extends TaskDriverTest {
    
    private $task;
    
    private $cookies = array(
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3'        
    );
    
    public function setUp() {
        parent::setUp();
        $this->task = $this->getTask('http://example.com/', array(
            'cookies' => json_encode($this->cookies)
        ));
    }
    
    public function testCookiesAreSetOnRootWebPageRequest() {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 200 OK'
        )));
        
        $this->assertEquals(0, $this->getTaskService()->perform($this->task));
        
        foreach ($this->getHttpClientService()->getHistory()->getAll() as $httpTransaction) {
            $this->assertEquals($this->cookies, $httpTransaction['request']->getCookies());
        }
    }
    
}
