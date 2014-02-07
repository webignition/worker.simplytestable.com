<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity\TaskDriverTest;

class DefaultTest extends TaskDriverTest {
    

    /**
     * @group standard
     */    
    public function testPerformWithNoBrokenLinks() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $task = $this->getDefaultTask();

        $this->assertEquals(0, $this->getTaskService()->perform($task));        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());

        $this->assertEquals(array(
            array(
                'context' => '<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css" rel="stylesheet">',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css'                
            ),
            array(
                'context' => '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'                
            ),
            array(
                'context' => '<a href="/">Home</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/'                
            ),
            array(
                'context' => '<a href="/articles/">Articles</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/articles/'                
            ),
            array(
                'context' => '<img src="http://www.gravatar.com/avatar/28d3858fd164c191d05954e114adeb9a?s=96">',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://www.gravatar.com/avatar/28d3858fd164c191d05954e114adeb9a?s=96'                
            ),
            array(
                'context' => '<a href="https://github.com/webignition">github.com/webignition</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'https://github.com/webignition'                
            ),
            array(
                'context' => '<a href="http://www.linkedin.com/in/joncram">linkedin.com/in/joncram</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://www.linkedin.com/in/joncram'                
            ),
        ), json_decode($task->getOutput()->getOutput(), true));
    }
    
    /**
     * @group standard
     */    
    public function testPerformWithOneHttp404() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $task = $this->getDefaultTask();

        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
      
        $this->assertEquals(array(
            array(
                'context' => '<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css" rel="stylesheet">',
                'state' => 404,
                'type' => 'http',
                'url' => 'http://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css'                
            ),
            array(
                'context' => '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'                
            ),
            array(
                'context' => '<a href="/">Home</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/'                
            ),
            array(
                'context' => '<a href="/articles/">Articles</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/articles/'                
            ),
            array(
                'context' => '<img src="http://www.gravatar.com/avatar/28d3858fd164c191d05954e114adeb9a?s=96">',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://www.gravatar.com/avatar/28d3858fd164c191d05954e114adeb9a?s=96'                
            ),
            array(
                'context' => '<a href="https://github.com/webignition">github.com/webignition</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'https://github.com/webignition'                
            ),
            array(
                'context' => '<a href="http://www.linkedin.com/in/joncram">linkedin.com/in/joncram</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://www.linkedin.com/in/joncram'                
            ),
        ), json_decode($task->getOutput()->getOutput(), true));
    } 
    
    
    /**
     * @group standard
     */      
    public function testPerformWithExcludedUrls() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $task = $this->getTask('http://example.com/', array(
            'excluded-urls' => array(
                'http://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css'
            )            
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());

        $this->assertEquals(array(
            array(
                'context' => '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'                
            ),
            array(
                'context' => '<a href="/">Home</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/'                
            ),
            array(
                'context' => '<a href="/articles/">Articles</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/articles/'                
            ),
            array(
                'context' => '<img src="http://www.gravatar.com/avatar/28d3858fd164c191d05954e114adeb9a?s=96">',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://www.gravatar.com/avatar/28d3858fd164c191d05954e114adeb9a?s=96'                
            ),
            array(
                'context' => '<a href="https://github.com/webignition">github.com/webignition</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'https://github.com/webignition'                
            ),
            array(
                'context' => '<a href="http://www.linkedin.com/in/joncram">linkedin.com/in/joncram</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://www.linkedin.com/in/joncram'                
            ),
        ), json_decode($task->getOutput()->getOutput(), true));        
    }    
    
    
    /**
     * @group standard
     */      
    public function testPerformWithExcludedDomains() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $task = $this->getTask('http://example.com/', array(
            'excluded-domains' => array(
                'www.gravatar.com',
                'www.linkedin.com'
            )         
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));   
        $this->assertEquals(0, $task->getOutput()->getErrorCount());

        $this->assertEquals(array(
            array(
                'context' => '<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css" rel="stylesheet">',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css'                
            ),
            array(
                'context' => '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'                
            ),
            array(
                'context' => '<a href="/">Home</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/'                
            ),
            array(
                'context' => '<a href="/articles/">Articles</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/articles/'                
            ),
            array(
                'context' => '<a href="https://github.com/webignition">github.com/webignition</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'https://github.com/webignition'                
            ),
        ), json_decode($task->getOutput()->getOutput(), true));        
    } 
    
    
    /**
     * @group standard
     */    
    public function testPerformWithHttpAuthParameters() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $task = $this->getTask('http://example.com/', array(
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'            
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());

        $this->assertEquals(array(
            array(
                'context' => '<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css" rel="stylesheet">',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css'                
            ),
            array(
                'context' => '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'                
            ),
            array(
                'context' => '<a href="/">Home</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/'                
            ),
            array(
                'context' => '<a href="/articles/">Articles</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/articles/'                
            ),
            array(
                'context' => '<img src="http://www.gravatar.com/avatar/28d3858fd164c191d05954e114adeb9a?s=96">',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://www.gravatar.com/avatar/28d3858fd164c191d05954e114adeb9a?s=96'                
            ),
            array(
                'context' => '<a href="https://github.com/webignition">github.com/webignition</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'https://github.com/webignition'                
            ),
            array(
                'context' => '<a href="http://www.linkedin.com/in/joncram">linkedin.com/in/joncram</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://www.linkedin.com/in/joncram'                
            ),
        ), json_decode($task->getOutput()->getOutput(), true));
    }    
    
}
