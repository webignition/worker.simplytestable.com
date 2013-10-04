<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\LinkIntegrity;

use SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\PerformCommandTaskTypeTest;

class PerformCommandLinkIntegrityTest extends PerformCommandTaskTypeTest {
    
    const TASK_TYPE_NAME = 'Link integrity';
    
    protected function getTaskTypeName() {
        return self::TASK_TYPE_NAME;
    }
    
    
    /**
     * @group standard
     */    
    public function testPerformWithNoBrokenLinks() {
        $this->clearMemcacheHttpCache();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());
        
        $task = $this->getTaskService()->getById($taskObject->id);

        $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        )));   
        
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
        $this->clearMemcacheHttpCache();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());
        
        $task = $this->getTaskService()->getById($taskObject->id);

        $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        )));     
        
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
}