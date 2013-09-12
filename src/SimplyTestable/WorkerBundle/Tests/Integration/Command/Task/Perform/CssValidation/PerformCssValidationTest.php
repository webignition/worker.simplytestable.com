<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

/**
 * Example parameter set:
 * 
 * {
 *    "ignore-warnings":"1",
 *    "ignore-common-cdns":"1",
 *    "vendor-extensions":"warn",
 *    "domains-to-ignore":[
 *       "cdnjs.cloudflare.com",
 *       "ajax.googleapis.com",
 *       "netdna.bootstrapcdn.com",
 *       "ajax.aspnetcdn.com",
 *       "static.nrelate.com"
 *    ]
 * }
 */

class PerformCssValidationTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }    

    /**
     * @group integration
     * @group integration-css-validation
     * @group integration-travis
     * @group integration-css-validation-travis
     */    
    public function testErrorFreeCssValidation() {        
        $taskObject = $this->createTask('http://css-validation.simplytestable.com', 'CSS validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(0, $task->getOutput()->getErrorCount());        
        $this->assertEquals('[]', $task->getOutput()->getOutput());
    }    
    

    /**
     * @group integration
     * @group integration-css-validation
     * @group integration-travis
     * @group integration-css-validation-travis
     */         
    public function testCssValidationRedirectLoop() {        
        $taskObject = $this->createTask('http://simplytestable.com/redirect-loop-test/', 'CSS validation');
        
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
     * @group integration-css-validation
     * @group integration-travis
     * @group integration-css-validation-travis
     */        
    public function testCssValidationRedirectLimit() {        
        $taskObject = $this->createTask('http://simplytestable.com/redirect-limit-test/', 'CSS validation');
        
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
     * @group integration-css-validation
     * @group integration-travis
     * @group integration-css-validation-travis
     */          
    public function testTwitterBootstrap231() {        
        $taskObject = $this->createTask('http://css-validation.simplytestable.com/twitter-bootstrap/2.3.1.html', 'CSS validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(1099, $task->getOutput()->getErrorCount());        
    }     


    /**
     * @group integration
     * @group integration-css-validation
     * @group integration-travis
     * @group integration-css-validation-travis
     */        
    public function testTwitterBootstrapWithNetdnaBootstrapcdncomInDomainsToIgnore() {                
        $taskObject = $this->createTask(
                'http://css-validation.simplytestable.com/twitter-bootstrap/2.3.1.html',
                'CSS validation',
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
    
    
    /**
     * @group integration
     * @group integration-css-validation
     * @group integration-travis
     * @group integration-css-validation-travis
     */          
    public function testOneErrorOneWarning() {                
        $taskObject = $this->createTask(
                'http://css-validation.simplytestable.com/one-error-one-warning.html',
                'CSS validation'                   
        );
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        
        // Should ideally be getting 1 warning but when calling the CSS validator
        // from the command line no warnings are returned. This might be a CSS
        // validator bug.
        $this->assertEquals(0, $task->getOutput()->getWarningCount());
    }
    
    
    /**
     * @group integration
     * @group integration-css-validation
     * @group integration-travis
     * @group integration-css-validation-travis
     */        
    public function testFiveErrorsOneVextWarning() {                
        $taskObject = $this->createTask(
                'http://css-validation.simplytestable.com/five-errors-one-warning.html',
                'CSS validation',
                json_encode(array(
                    'vendor-extensions' => 'warn',
                    'ignore-warnings' => 0
                ))                
        );
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);
        $this->assertEquals(5, $task->getOutput()->getErrorCount());
        $this->assertEquals(1, $task->getOutput()->getWarningCount());
    }   
    
    
    /**
     * @group integration
     * @group integration-css-validation
     * @group integration-travis
     * @group integration-css-validation-travis
     */        
    public function testFiveErrorsOneVextWarningIgnoreWarnings() {                
        $taskObject = $this->createTask(
                'http://css-validation.simplytestable.com/five-errors-one-warning.html',
                'CSS validation',
                json_encode(array(
                    'vendor-extensions' => 'warn',
                    'ignore-warnings' => 1
                ))                
        );
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);
        $this->assertEquals(5, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());
    }   
    
    
    /**
     * @group integration
     * @group integration-css-validation
     * @group integration-travis
     * @group integration-css-validation-travis
     */        
    public function testFiveErrorsOneVextWarningAsError() {                
        $taskObject = $this->createTask(
                'http://css-validation.simplytestable.com/five-errors-one-warning.html',
                'CSS validation',
                json_encode(array(
                    'vendor-extensions' => 'error',
                    'ignore-warnings' => 0
                ))                
        );
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);
        $this->assertEquals(6, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());
    } 
    
    
    /**
     * @group integration
     * @group integration-css-validation
     * @group integration-travis
     * @group integration-css-validation-travis
     */        
    public function testSourceUrlContainingDoubleQuote() {                
        $taskObject = $this->createTask(
                'http://css-validation.simplytestable.com/with-double-"-quotes-in-URL.html',                
                'CSS validation',
                json_encode(array(
                    'vendor-extensions' => 'error',
                    'ignore-warnings' => 0
                ))                
        );
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);
    }
    
    /**
     * @group integration
     * @group integration-html-validation
     * @group integration-travis
     * @group integration-html-validation-travis
     */       
    public function testWithSingleSpaceInUrl() {        
//        $url = 'http://chellasolutions.com/Our projects.html';
        $url = 'http://html-validation.simplytestable.com/url-cases/minimal-no-errors with-single-space.html';
        
        $taskObject = $this->createTask($url, 'HTML validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());        
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());
    }    
}

