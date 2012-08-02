<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;

use SimplyTestable\WorkerBundle\Tests\BaseTestCase;

class BaseControllerJsonTestCase extends BaseTestCase {   
    
    protected function createWebRequest() {
        $request = parent::createWebRequest();
        $request->headers->set('Accept', 'application/json');        
        return $request;
    }   
    
}
