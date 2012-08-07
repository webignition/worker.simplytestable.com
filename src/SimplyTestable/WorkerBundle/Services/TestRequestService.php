<?php
namespace SimplyTestable\WorkerBundle\Services;

use Symfony\Component\HttpFoundation\Request;

class TestRequestService extends RequestService {
    
    
    /**
     *
     * @param Request $request
     * @return \SimplyTestable\WorkerBundle\Services\RequestService 
     */
    public function setRequest(Request $request) {
        $this->request = null;        
        return $this;
    }
    
    
    /**
     *
     * @return Request
     */
    public function getRequest() {        
        if (!$this->hasRequest()) {
            parent::setRequest(\Symfony\Component\HttpFoundation\Request::createFromGlobals());
        }
        
        return parent::getRequest();
    }
    
}