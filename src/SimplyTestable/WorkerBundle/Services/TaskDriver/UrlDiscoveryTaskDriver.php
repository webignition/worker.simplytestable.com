<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;

class UrlDiscoveryTaskDriver extends WebResourceTaskDriver {
    
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';
    
    private $equivalentSchemes = array(
        'http',
        'https'
    );
    
    
    /**
     * 
     * @param \SimplyTestable\WorkerBundle\Entity\Task\Task $task
     * @return boolean
     */
    protected function isCorrectTaskType(Task $task) {        
        return $task->getType()->equals($this->getTaskTypeService()->getUrlDiscoveryTaskType());
    }
    
    
    /**
     * 
     * @return string
     */
    protected function hasNotSucceedHandler() {
        $this->response->setErrorCount(1);
        return json_encode($this->getWebResourceExceptionOutput());        
    }
    
    protected function isNotCorrectWebResourceTypeHandler() {
        $this->response->setHasBeenSkipped();
        $this->response->setErrorCount(0);
        return true;
    }
    
    
    protected function isBlankWebResourceHandler() {
        $this->response->setHasBeenSkipped();
        $this->response->setErrorCount(0);
        return true;        
    }
    
    
    protected function performValidation() {         
        $fragment = $this->webResource->getContent();        
        $finder = new HtmlDocumentLinkUrlFinder();
        $finder->getConfiguration()->setSourceContent($fragment);
        $finder->getConfiguration()->setSourceUrl($this->webResource->getUrl());
        $finder->getConfiguration()->setElementScope('a');
        $finder->getConfiguration()->enableIgnoreFragmentInUrlComparison();
        $finder->getUrlScopeComparer()->addEquivalentSchemes($this->equivalentSchemes);
        
        if ($this->task->hasParameter('scope')) {
            $finder->getConfiguration()->setUrlScope($this->task->getParameter('scope'));
        }

        return json_encode($finder->getUniqueUrls());       
    }
    
    /**
     * 
     * @return boolean
     */
    protected function isCorrectWebResourceType() {
        return $this->webResource instanceof WebPage;
    }
    
    
    /**
     *
     * @return \webignition\InternetMediaType\InternetMediaType 
     */
    protected function getOutputContentType()
    {
        $mediaTypeParser = new \webignition\InternetMediaType\Parser\Parser();
        return $mediaTypeParser->parse('application/json');
    }


    
}