<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions\BooleanOptions;

class ForinTest extends OptionOnOffTest {
    
    /**
     * @group standard
     */    
    public function testOff() {        
        $this->offTest(__CLASS__);
    } 
    
    /**
     * @group standard
     */    
    public function testOn() {        
        $this->onTest(__CLASS__);
    }
}
