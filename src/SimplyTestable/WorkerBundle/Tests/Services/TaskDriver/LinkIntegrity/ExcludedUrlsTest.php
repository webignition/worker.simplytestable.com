<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity;

class ExcludedUrlsTest extends ExpectedOutputTest {

    protected function getTaskParameters() {
        return [
            'excluded-urls' => [
                'http://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css'
            ]
        ];
    }

    protected function getExpectedErrorCount() {
        return 0;
    }

    protected function getExpectedOutput() {
        return array(
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
        );
    }
    
}
