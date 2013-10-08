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
    
    const EXCLUDED_URLS_PARAMETER_NAME = 'excluded-urls';
    
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
        
        if ($this->task->hasParameter(self::EXCLUDED_URLS_PARAMETER_NAME)) {
            $linkChecker->setUrlsToExclude($this->task->getParameter(self::EXCLUDED_URLS_PARAMETER_NAME));
        }
        
        $this->getWebResourceService()->getHttpClientService()->disablePlugin('Guzzle\Plugin\Backoff\BackoffPlugin');
        
        $this->getWebResourceService()->getHttpClientService()->get()->setUserAgent('ST Link Integrity Task Driver (http://bit.ly/RlhKCL)');             
        $linkChecker->setHttpClient($this->getWebResourceService()->getHttpClientService()->get());        
        $linkCheckResults = $linkChecker->getAll();
        $this->getWebResourceService()->getHttpClientService()->get()->setUserAgent(null);
        
        $this->getWebResourceService()->getHttpClientService()->enablePlugins();

        $this->response->setErrorCount(count($linkChecker->getErrored()));                
        return json_encode($this->getOutputOject($linkCheckResults));
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
     * @param array $linkCheckResults
     * @return \stdClass 
     */
    private function getOutputOject($linkCheckResults) {
        $outputObject = array();
        
        
        /* @var $linkState \webignition\HtmlDocumentLinkChecker\LinkCheckResult */
        foreach ($linkCheckResults as $linkCheckResult) {          
            $outputObject[] = array(
                'context' => $linkCheckResult->getContext(),
                'state' => $linkCheckResult->getLinkState()->getState(),
                'type' => $linkCheckResult->getLinkState()->getType(),
                'url' => $linkCheckResult->getUrl()
            );
        }
        
        return $outputObject;
    }
    
}