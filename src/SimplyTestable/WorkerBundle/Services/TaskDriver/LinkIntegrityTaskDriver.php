<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\HtmlDocument\LinkChecker\Configuration as LinkCheckerConfiguration;

class LinkIntegrityTaskDriver extends WebResourceTaskDriver {    
    
    const EXCLUDED_URLS_PARAMETER_NAME = 'excluded-urls';
    const EXCLUDED_DOMAINS_PARAMETER_NAME = 'excluded-domains';
    
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
        $linkChecker = $this->getLinkChecker();

        $linkCheckResults = $linkChecker->getAll();
        
        $this->getHttpClientService()->enablePlugins();

        $this->response->setErrorCount(count($linkChecker->getErrored()));                
        return json_encode($this->getOutputOject($linkCheckResults));
    }
    
    
    /**
     * 
     * @return \webignition\HtmlDocument\LinkChecker\LinkChecker
     */
    private function getLinkChecker() {
        $linkChecker = new LinkChecker();
        $linkChecker->setWebPage($this->webResource);
        $linkChecker->getConfiguration()->setHttpMethodList(array(
            LinkCheckerConfiguration::HTTP_METHOD_GET
        ));
        
        if ($this->task->hasParameter(self::EXCLUDED_URLS_PARAMETER_NAME)) {
            $linkChecker->getConfiguration()->setUrlsToExclude($this->task->getParameter(self::EXCLUDED_URLS_PARAMETER_NAME));
        }        
        
        if ($this->task->hasParameter(self::EXCLUDED_DOMAINS_PARAMETER_NAME)) {
            $linkChecker->getConfiguration()->setDomainsToExclude($this->task->getParameter(self::EXCLUDED_DOMAINS_PARAMETER_NAME));
        }  
        
        $linkChecker->getConfiguration()->enableToggleUrlEncoding();
        $linkChecker->getConfiguration()->disableRetryOnBadResponse();
        
        $this->getHttpClientService()->disablePlugin('Guzzle\Plugin\Backoff\BackoffPlugin');
        
        $linkChecker->getConfiguration()->setUserAgents($this->getProperty('user-agents'));
        
        $baseRequest = $this->getHttpClientService()->get()->createRequest('GET', 'http://www.example.com/', null, null, array(
            'timeout' => 10
        ));
        
        if ($this->task->hasParameter('http-auth-username') || $this->task->hasParameter('http-auth-password')) {
            $baseRequest->setAuth(
                $this->task->hasParameter('http-auth-username') ? $this->task->getParameter('http-auth-username') : '',
                $this->task->hasParameter('http-auth-password') ? $this->task->getParameter('http-auth-password') : '',
                'any'
            );
        }        
        
        $linkChecker->getConfiguration()->setBaseRequest($baseRequest);

        return $linkChecker;       
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