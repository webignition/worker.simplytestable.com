<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation;

class SingleChildStylesheetCurlErrorTest extends TransportErrorTest {
    
    protected function getFixtureSet() {
        return $this->buildHttpFixtureSet(array(
            file_get_contents($this->getFixturesDataPath() . '/HttpResponses/1'),
            'CURL/' . str_replace('test', '', $this->getName().' Not-relevant worded error')
        ));
    }
    
    protected function getOutputTemplate() {
        return '[{"message":"http-retrieval-curl-code-{{failure-code}}","context":"","line_number":0,"type":"error","ref":"http:\/\/example.com\/style.css"}]';
    }    
    
    
    /**
     * @group standard
     */       
    public function test6() {
        $this->assertRetrievalFailureCodeMatchesReceivedResponse(str_replace('test', '', $this->getName()));
    }    
    
    
    /**
     * @group standard
     */       
    public function test28() {
        $this->assertRetrievalFailureCodeMatchesReceivedResponse(str_replace('test', '', $this->getName()));
    }  

}