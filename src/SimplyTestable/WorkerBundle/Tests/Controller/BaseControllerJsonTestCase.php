<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;

use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class BaseControllerJsonTestCase extends BaseSimplyTestableTestCase {   
    
    protected function createWebRequest() {
        $request = parent::createWebRequest();
        $request->headers->set('Accept', 'application/json');        
        return $request;
    }   
    
}
