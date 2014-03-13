<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\CookieParameters;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\TaskDriverTest;

abstract class CookieParametersTest extends TaskDriverTest {
    
    protected $task;
    
    protected $cookies = array(
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3'        
    );
    
    public function setUp() {
        parent::setUp();
        $this->task = $this->getTask('http://example.com/', array(
            'cookies' => json_encode($this->cookies)
        ));
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath('../CssValidatorResponse/1'))
        );          
    }
    
    public function testCookiesAreSetOnAllRequests() {        
        $this->assertEquals(0, $this->getTaskService()->perform($this->task));
        
        foreach ($this->getHttpClientService()->getHistory()->getAll() as $httpTransaction) {
            $this->assertEquals($this->cookies, $httpTransaction['request']->getCookies());
        }
    }
    
}
