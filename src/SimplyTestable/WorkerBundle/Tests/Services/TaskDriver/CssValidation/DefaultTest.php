<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\TaskDriverTest;

class DefaultTest extends TaskDriverTest {
    
    public function setUp() {
        parent::setUp();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
        );        
    }  
    
    
    /**
     * @group standard
     */     
    public function testIgnoreFalseBackgroundImageDataUrlIssues() {     
        $task = $this->getTask('http://example.com/', array(
            'ignore-warnings' => true            
        ));        
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    }    
    
    
    /**
     * @group standard
     */     
    public function testVendorExtensionSeverityLevelIgnore() {             
        $task = $this->getTask('http://example.com/', array(
            'vendor-extensions' => 'ignore'          
        ));        
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    } 
    
    
    /**
     * @group standard
     */     
    public function testChildCssResourceUnknownMimeType() {              
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task)); 
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());
        
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput());        
        $this->assertEquals('invalid-content-type:invalid/made-it-up', $decodedTaskOutput[0]->message);
    }    
   
    
    /**
     * @group standard
     */     
    public function testRootWebResourceUnknownException() {
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task)); 
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());
        
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput());        
        $this->assertEquals('css-validation-exception-unknown', $decodedTaskOutput[0]->class);
    }
    
    
    /**
     * @group standard
     */     
    public function testRootWebResourceHasMangledMarkup() {
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task)); 
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());
    }    

}
