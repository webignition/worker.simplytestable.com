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
     * @group integration-travis
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
     * @group integration-travis
     */      
    public function testJsStaticAnalysisRedirectLimit() {        
        $taskObject = $this->createTask('http://simplytestable.com/redirect-limit-test/', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(1, $task->getOutput()->getErrorCount());        
        
        $this->assertEquals('{"messages":[{"message":"Redirect limit of 4 redirects reached","messageId":"http-retrieval-redirect-limit-reached","type":"error"}]}', $task->getOutput()->getOutput());
    }     
    
    
    /**
     * @group integration
     */       
    public function testOneInlineError() {        
        $taskObject = $this->createTask('http://js-static-analysis.simplytestable.com/one-inline-error.html', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals('{"http:\/\/js-static-analysis.simplytestable.com\/assets\/js\/app.js":{"statusLine":"\/tmp\/394c3b5df64d0016a78c6946bbfa7bc9","entries":[]},"9ce0130bd698c62e66f3356818aefff5":{"statusLine":"\/tmp\/9ce0130bd698c62e66f3356818aefff5","entries":[{"headerLine":{"errorNumber":1,"errorMessage":"Missing \'use strict\' statement."},"fragmentLine":{"fragment":"};","lineNumber":5,"columnNumber":1}}]}}', $task->getOutput()->getOutput());     
    } 
    
    /**
     * @group integration
     */       
    public function testOneReferencedError() {        
        $taskObject = $this->createTask('http://js-static-analysis.simplytestable.com/one-referenced-error.html', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(1, $task->getOutput()->getErrorCount());        
        $this->assertEquals('{"http:\/\/js-static-analysis.simplytestable.com\/assets\/js\/app.js":{"statusLine":"\/tmp\/394c3b5df64d0016a78c6946bbfa7bc9","entries":[]},"http:\/\/js-static-analysis.simplytestable.com\/assets\/js\/one-error.js":{"statusLine":"\/tmp\/9ce0130bd698c62e66f3356818aefff5","entries":[{"headerLine":{"errorNumber":1,"errorMessage":"Missing \'use strict\' statement."},"fragmentLine":{"fragment":"};","lineNumber":5,"columnNumber":1}}]}}', $task->getOutput()->getOutput());     
    } 
    
    
    /**
     * @group integration
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
        $this->assertEquals(51, $task->getOutput()->getErrorCount());                
    }  
    
    
    /**
     * @group integration
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

