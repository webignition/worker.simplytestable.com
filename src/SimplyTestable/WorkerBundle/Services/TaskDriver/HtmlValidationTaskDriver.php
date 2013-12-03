<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentTypeIdentifier\HtmlDocumentTypeIdentifier;

class HtmlValidationTaskDriver extends WebResourceTaskDriver {    
    
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';
    
    public function __construct() {
        $this->canCacheValidationOutput = true;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\WorkerBundle\Entity\Task\Task $task
     * @return boolean
     */
    protected function isCorrectTaskType(Task $task) {        
        return $task->getType()->equals($this->getTaskTypeService()->getHtmlValidationTaskType());
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
        
        $htmlDocumentTypeIdentifier = new HtmlDocumentTypeIdentifier();
        $htmlDocumentTypeIdentifier->setHtml($fragment);
        
        if (!$htmlDocumentTypeIdentifier->hasDocumentType()) {
            $this->response->setErrorCount(1);
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);            
            
            if ($this->isMarkup($fragment)) {
                return json_encode($this->getMissingDocumentTypeOutput());
            } else {                
                return json_encode($this->getIsNotMarkupOutput($fragment));
            }
        }
        
        if (!$htmlDocumentTypeIdentifier->hasValidDocumentType()) {            
            $this->response->setErrorCount(1);
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
            return json_encode($this->getInvalidDocumentTypeOutput($htmlDocumentTypeIdentifier->getDocumentTypeString()));             
        }
        
        $this->getProperty('html-validator-wrapper')->createConfiguration(array(
            'documentUri' => 'file:' . $this->storeTmpFile($fragment),
            'validatorPath' => $this->getProperty('validator-path')
        ));

        $output = $this->getProperty('html-validator-wrapper')->validate();       

        $outputObject = new \stdClass();
        $outputObject->messages = $output->getMessages();
        
        $this->response->setErrorCount((int)$output->getErrorCount());
        //$this->response->setWarningCount($output->getWarningCount());

        return json_encode($outputObject);      
    }
    
    
    /**
     * 
     * @param string $content
     * @return string
     */
    private function storeTmpFile($content) {
        $filename = sys_get_temp_dir() . '/' . md5($content) . '.html';
        if (!file_exists($filename)) {
            file_put_contents($filename, $content);
        }
        
        return $filename;
    }
    
    
    /**
     * 
     * @param string $fragment
     * @return boolean
     */
    private function isMarkup($fragment) {
        return strip_tags($fragment) !== $fragment;
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
    
}