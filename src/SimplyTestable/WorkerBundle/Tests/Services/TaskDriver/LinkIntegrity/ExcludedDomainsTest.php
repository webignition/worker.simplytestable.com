<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity;

class ExcludedDomainsTest extends ExpectedOutputTest {

    protected function getTaskParameters() {
        return [
            'excluded-domains' => array(
                'www.gravatar.com',
                'www.linkedin.com'
            )
        ];
    }

    protected function getExpectedErrorCount() {
        return 0;
    }

    protected function getExpectedOutput() {
        return array(
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
        );
    }


    
}
