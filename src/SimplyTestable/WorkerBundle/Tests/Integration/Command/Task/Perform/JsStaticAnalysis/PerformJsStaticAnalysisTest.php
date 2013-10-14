<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\JsStaticAnalysis;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

/**
 * Example parameter set:
 *
 * {
 *    "ignore-common-cdns":"1",
 *    "domains-to-ignore":[
 *       "cdnjs.cloudflare.com",
 *       "ajax.googleapis.com",
 *       "netdna.bootstrapcdn.com",
 *       "ajax.aspnetcdn.com",
 *       "static.nrelate.com"
 *    ]
}
 */

class PerformJsStaticAnalysisTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }    

    /**
     * @group integration
     * @group integration-js-static-analysis
     * @group integration-travis
     * @group integration-js-static-analysis-travis
     */      
    public function testErrorFreeJsStaticAnalysis() {        
        $taskObject = $this->createTask('http://js-static-analysis.simplytestable.com', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());        
        $this->assertEquals('{"http:\/\/js-static-analysis.simplytestable.com\/assets\/js\/app.js":{"statusLine":"\/tmp\/394c3b5df64d0016a78c6946bbfa7bc9","entries":[]}}', $task->getOutput()->getOutput());
    }    
    

    /**
     * @group integration
     * @group integration-js-static-analysis
     * @group integration-travis
     * @group integration-js-static-analysis-travis
     */       
    public function testJsStaticAnalysisRedirectLoop() {        
        $taskObject = $this->createTask('http://simplytestable.com/redirect-loop-test/', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(1, $task->getOutput()->getErrorCount());        
        
        $this->assertEquals('{"messages":[{"message":"Redirect loop detected","messageId":"http-retrieval-redirect-loop","type":"error"}]}', $task->getOutput()->getOutput());
    }  
    
    
    /**
     * @group integration
     * @group integration-js-static-analysis
     * @group integration-travis
     * @group integration-js-static-analysis-travis
     */      
    public function testJsStaticAnalysisRedirectLimit() {        
        $taskObject = $this->createTask('http://simplytestable.com/redirect-limit-test/', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(1, $task->getOutput()->getErrorCount());        
        
        $this->assertEquals('{"messages":[{"message":"Redirect limit of ' . $this->getWebResourceService()->getMaxRedirects() . ' redirects reached","messageId":"http-retrieval-redirect-limit-reached","type":"error"}]}', $task->getOutput()->getOutput());
    }     
    
    
    /**
     * @group integration
     * @group integration-js-static-analysis
     * @group integration-travis
     * @group integration-js-static-analysis-travis
     */          
    public function testOneInlineError() {        
        $taskObject = $this->createTask('http://js-static-analysis.simplytestable.com/one-inline-error.html', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals('{"http:\/\/js-static-analysis.simplytestable.com\/assets\/js\/app.js":{"statusLine":"\/tmp\/394c3b5df64d0016a78c6946bbfa7bc9","entries":[]},"27b65b1ff31a7d0adc705d8eac9764e4":{"statusLine":"\/tmp\/27b65b1ff31a7d0adc705d8eac9764e4","entries":[{"headerLine":{"errorNumber":1,"errorMessage":"Missing \'use strict\' statement."},"fragmentLine":{"fragment":"    return true;","lineNumber":5,"columnNumber":5}}]}}', $task->getOutput()->getOutput());     
    } 
    
    /**
     * @group integration
     * @group integration-js-static-analysis
     * @group integration-travis
     * @group integration-js-static-analysis-travis
     */         
    public function testOneReferencedError() {        
        $taskObject = $this->createTask('http://js-static-analysis.simplytestable.com/one-referenced-error.html', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(1, $task->getOutput()->getErrorCount());        
        $this->assertEquals('{"http:\/\/js-static-analysis.simplytestable.com\/assets\/js\/app.js":{"statusLine":"\/tmp\/394c3b5df64d0016a78c6946bbfa7bc9","entries":[]},"http:\/\/js-static-analysis.simplytestable.com\/assets\/js\/one-error.js":{"statusLine":"\/tmp\/cf1dee16b6b54c74e9f5085faeb5965c","entries":[{"headerLine":{"errorNumber":1,"errorMessage":"Missing \'use strict\' statement."},"fragmentLine":{"fragment":"    return true;","lineNumber":5,"columnNumber":5}}]}}', $task->getOutput()->getOutput());     
    } 
    
    
    /**
     * @group integration
     * @group integration-js-static-analysis
     * @group integration-travis
     * @group integration-js-static-analysis-travis
     */           
    public function testTwitterBootstrap231Default() {        
        $taskObject = $this->createTask(
                'http://js-static-analysis.simplytestable.com/twitter-bootstrap/2.3.1.html',
                'JS static analysis'
        );
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(31, $task->getOutput()->getErrorCount());                
    }  
    
    
    /**
     * @group integration
     * @group integration-js-static-analysis
     * @group integration-travis
     * @group integration-js-static-analysis-travis
     */         
    public function testTwitterBootstrap231NetdnaBootstrapcdnComInDomainsToIgnore() {        
        $taskObject = $this->createTask(
                'http://js-static-analysis.simplytestable.com/twitter-bootstrap/2.3.1.html',
                'JS static analysis',
                json_encode(array(
                    'domains-to-ignore' => array(
                        'netdna.bootstrapcdn.com'
                    )
                )) 
        );
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());                
    }     
}

