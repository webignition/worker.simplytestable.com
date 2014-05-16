<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation;

class SingleChildStylesheetHttpErrorTest extends TransportErrorTest {
    
    protected function getFixtureSet() {
        return $this->buildHttpFixtureSet(array(
            file_get_contents($this->getFixturesDataPath() . '/HttpResponses/1'),
            'HTTP/1.0 ' . str_replace('test', '', $this->getName()),
            'HTTP/1.0 ' . str_replace('test', '', $this->getName())
        ));
    }
    
    protected function getOutputTemplate() {
        return '[{"message":"http-retrieval-{{failure-code}}","context":"","ref":"http:\/\/example.com\/style.css","line_number":0,"type":"error"}]';
    }    
    
    
    /**
     * @group standard
     */       
    public function test401() {
        $this->assertRetrievalFailureCodeMatchesReceivedResponse(str_replace('test', '', $this->getName()));
    }    
    
    
    /**
     * @group standard
     */       
    public function test404() {
        $this->assertRetrievalFailureCodeMatchesReceivedResponse(str_replace('test', '', $this->getName()));
    }
    
    
    /**
     * @group standard
     */       
    public function test500() {
        $this->assertRetrievalFailureCodeMatchesReceivedResponse(str_replace('test', '', $this->getName()));
    }  

}
