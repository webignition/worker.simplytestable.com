<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResource;
use webignition\WebResource\JsonDocument\JsonDocument;
use SimplyTestable\WorkerBundle\Services\TaskDriver\W3cValidatorErrorParser;
use webignition\HtmlDocumentTypeIdentifier\HtmlDocumentTypeIdentifier;

class LinkIntegrityTaskDriver extends WebResourceTaskDriver {    
    
    public function __construct() {
        $this->canCacheValidationOutput = false;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\WorkerBundle\Entity\Task\Task $task
     * @return boolean
     */
    protected function isCorrectTaskType(Task $task) {        
        return $task->getType()->equals($this->getTaskTypeService()->getLinkIntegrityTaskType());
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
        $linkChecker = new \webignition\HtmlDocumentLinkChecker\HtmlDocumentLinkChecker();
        $linkChecker->setWebPage($this->webResource);
        
        $this->getWebResourceService()->getHttpClientService()->get()->setUserAgent('SimplyTestable-Link-Integrity-Task-Driver/0.1 (http://simplytestable.com/)');             
        $linkChecker->setHttpClient($this->getWebResourceService()->getHttpClientService()->get());        
        $erroredLinks = $linkChecker->getErrored();
        $this->getWebResourceService()->getHttpClientService()->get()->setUserAgent(null);
        
        $this->response->setErrorCount(count($erroredLinks));        
        return json_encode($this->getOutputOject($erroredLinks));
    }
    
    
    protected function getMissingDocumentTypeOutput() {        
        $outputObjectMessage = new \stdClass();
        $outputObjectMessage->message = 'No doctype';
        $outputObjectMessage->messageId = 'document-type-missing';
        $outputObjectMessage->type = 'error';
        
        $outputObject = new \stdClass();
        $outputObject->messages = array($outputObjectMessage);        
        
        return $outputObject;
    }      

    protected function getIsNotMarkupOutput($fragment) {        
        $outputObjectMessage = new \stdClass();
        $outputObjectMessage->message = 'Not markup';
        $outputObjectMessage->messageId = 'document-is-not-markup';
        $outputObjectMessage->type = 'error';
        $outputObjectMessage->fragment = $fragment;
        
        $outputObject = new \stdClass();
        $outputObject->messages = array($outputObjectMessage);        
        
        return $outputObject;
    }      
    
    protected function getInvalidDocumentTypeOutput($documentType) {        
        $outputObjectMessage = new \stdClass();
        $outputObjectMessage->message = $documentType;
        $outputObjectMessage->messageId = 'document-type-invalid';
        $outputObjectMessage->type = 'error';
        
        $outputObject = new \stdClass();
        $outputObject->messages = array($outputObjectMessage);        
        
        return $outputObject;
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


    
    /**
     *
     * @param array $erroredLinks
     * @return \stdClass 
     */
    private function getOutputOject($erroredLinks) {
        $outputObject = array();
        
        
        /* @var $linkState \webignition\HtmlDocumentLinkChecker\LinkCheckResult */
        foreach ($erroredLinks as $linkState) {          
            $outputObject[] = array(
                'context' => $linkState->getContext(),
                'state' => $linkState->getLinkState()->getState(),
                'type' => $linkState->getLinkState()->getType(),
                'url' => $linkState->getUrl()
            );
        }
        
        return $outputObject;
    }
    
}