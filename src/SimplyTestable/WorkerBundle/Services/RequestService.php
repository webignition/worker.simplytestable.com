<?php
namespace SimplyTestable\WorkerBundle\Services;

use Symfony\Component\HttpFoundation\Request;

class RequestService {
    
    /**
     *
     * @var Request
     */
    private $request;
    
    
    /**
     *
     * @param Request $request
     * @return \SimplyTestable\WorkerBundle\Services\RequestService 
     */
    public function setRequest(Request $request) {
        $this->request = $request;
        return $this;
    }
    
    
    /**
     *
     * @return Request
     */
    public function getRequest() {
        return $this->request;
    }
    
    
    /**
     *
     * @return boolean
     */
    protected function hasRequest() {
        return !is_null($this->request);
    }
    
}