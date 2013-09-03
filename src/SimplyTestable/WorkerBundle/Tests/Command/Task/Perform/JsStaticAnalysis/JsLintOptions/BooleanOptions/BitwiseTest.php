<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\JsStaticAnalysis\JsLintOptions\BooleanOptions;

class BitwiseTest extends OptionOnOffTest {
    
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
