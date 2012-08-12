<?php
namespace SimplyTestable\WorkerBundle\Model;

/**
 * 
 */
class WebPage extends WebResource
{
    
    public function __construct($url, $content) {
        parent::__construct($url, 'text/html', $content);
    }
}