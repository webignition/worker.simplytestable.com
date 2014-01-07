<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\CssValidation;

use SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\PerformCommandTaskTypeTest;

class PerformCommandCssValidationTest extends PerformCommandTaskTypeTest {
    
    const TASK_TYPE_NAME = 'CSS validation';
    
    protected function getTaskTypeName() {
        return self::TASK_TYPE_NAME;
    }
    

    /**
     * @group standard
     */        
    public function testDomainsToIgnoreNotSet() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
        );        
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());         
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(9, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    }    
    
    /**
     * @group standard
     */        
    public function testDomainsToIgnoreOneDomainOfThree() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
        );        
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName(), json_encode(array(
            'domains-to-ignore' => array(
                'one.cdn.example.com'
            )            
        )));         
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(6, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    }  
    
  
    /**
     * @group standard
     */     
    public function testDomainsToIgnoreTwoDomainsOfThree() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
        );        
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName(), json_encode(array(
            'domains-to-ignore' => array(
                'one.cdn.example.com',
                'two.cdn.example.com'
            )            
        )));         
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(3, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    } 
    
    
    /**
     * @group standard
     */     
    public function testIgnoreWarningsNotSet() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
        );        
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());         
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
        $this->assertEquals(3, $task->getOutput()->getWarningCount());     
    } 
    
    
    /**
     * @group standard
     */     
    public function testIgnoreWarnings() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
        );        
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName(), json_encode(array(
            'ignore-warnings' => true
        )));         
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    }    
    
    
    /**
     * @group standard
     */     
    public function testIgnoreFalseBackgroundImageDataUrlIssues() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
        );        
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName(), json_encode(array(
            'ignore-warnings' => true
        )));         
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    }    
    
    
    /**
     * @group standard
     */     
    public function testVendorExtensionSeverityLevelIgnore() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
        );        
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName(), json_encode(array(
            'vendor-extensions' => 'ignore'
        )));         
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    } 
    
    
    /**
     * @group standard
     */     
    public function testChildCssResourceUnknownMimeType() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
        );        
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());         
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());
        
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput());        
        $this->assertEquals('invalid-content-type:invalid/made-it-up', $decodedTaskOutput[0]->message);
    }    
   
    
    /**
     * @group standard
     */     
    public function testRootWebResourceUnknownException() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
        );        
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());         
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());
        
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput());        
        $this->assertEquals('css-validation-exception-unknown', $decodedTaskOutput[0]->class);
    }
    
    
    /**
     * @group standard
     */     
    public function testRootWebResourceHasMangledMarkup() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName() . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/CssValidatorResponse/1'))
        );        
        
        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());         
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());
    }
}
