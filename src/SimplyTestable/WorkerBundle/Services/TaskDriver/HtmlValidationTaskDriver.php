<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResource;
use webignition\WebResource\JsonDocument\JsonDocument;
use SimplyTestable\WorkerBundle\Services\TaskDriver\W3cValidatorErrorParser;
use webignition\HtmlDocumentTypeIdentifier\HtmlDocumentTypeIdentifier;

class HtmlValidationTaskDriver extends WebResourceTaskDriver {    
    
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\TaskDriver\W3cValidatorErrorParser 
     */
    private $validatorErrorCollectionParser = null;
    
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
            return json_encode($this->getMissingDocumentTypeOutput($htmlDocumentTypeIdentifier->getDocumentTypeString()));             
        }
        
        if (!$htmlDocumentTypeIdentifier->hasValidDocumentType()) {            
            $this->response->setErrorCount(1);
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
            return json_encode($this->getInvalidDocumentTypeOutput($htmlDocumentTypeIdentifier->getDocumentTypeString()));             
        }        
        
        $characterEncoding = ($this->webResource->getIsDocumentCharacterEncodingValid()) ? $this->webResource->getCharacterEncoding() : self::DEFAULT_CHARACTER_ENCODING;
        
        $requestPostFields = array(
            'fragment' => $fragment,
            'output' => 'json'
        );
        
        if (!is_null($characterEncoding)) {
            $requestPostFields['charset'] = $characterEncoding;
        }        
        
        $validationRequest = $this->getWebResourceService()->getHttpClientService()->postRequest(
                $this->getProperty('validator-url'),
                null,
                $requestPostFields
        );        
        $validationRequest->getCurlOptions()->add(CURLOPT_TIMEOUT, 300);
        
        /* @var $validationResponse JsonDocument */
        $validationResponse = $this->getWebResourceService()->get($validationRequest);
        
        if ($validationResponse->getContentType()->getTypeSubtypeString() == 'text/html') {
            // HTML response, the validator failed to validate
            $outputObject = $this->getW3cValidatorErrorCollectionParser()->getOutputObject($validationResponse);
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
            $this->response->setErrorCount(1);            
            $this->canCacheValidationOutput = false;
        } else {
            // Regular JSON output
            $outputObject = $this->getOutputOject($validationResponse->getContentObject());
            $this->response->setHasSucceeded();
            
            $errorCount = 0;
            foreach ($validationResponse->getContentObject()->messages as $message) {
                if ($message->type == 'error') {
                    $errorCount++;
                }
            }
            
            $this->response->setErrorCount($errorCount);
            $this->canCacheValidationOutput = true;
        }
        
        return json_encode($outputObject);         
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
     * @param \stdClass $validationResponseObject
     * @return \stdClass 
     */
    private function getOutputOject(\stdClass $validationResponseObject) {
        $outputObject = new \stdClass();
        $outputObject->messages = array();
        
        foreach ($validationResponseObject->messages as $validationMessageObject) {
            $outputObject->messages[] = $this->getOutputObjectMessage($validationMessageObject);
        }
        
        return $outputObject;
    }
    
    
    /**
     *
     * @param \stdClass $validationMessageObject
     * @return \stdClass 
     */
    private function getOutputObjectMessage(\stdClass $validationMessageObject) {
        $requiredProperties = array(
            'lastLine',
            'lastColumn',
            'message',
            'messageid',            
            'type'
        );
        
        $outputObjectMessage = new \stdClass;
        
        foreach ($requiredProperties as $requiredPropertyName) {
            if (isset($validationMessageObject->$requiredPropertyName)) {
                $outputObjectMessage->$requiredPropertyName = $validationMessageObject->$requiredPropertyName;
            }
        }
        
        return $outputObjectMessage;
    }
    
    
    /**
     *
     * @return SimplyTestable\WorkerBundle\Services\TaskDriver\W3cValidatorErrorCollectionParser
     */
    private function getW3cValidatorErrorCollectionParser() {
        if (is_null($this->validatorErrorCollectionParser)) {            
            $className = $this->getProperty('validator-error-collection-parser-class');
            $this->validatorErrorCollectionParser = new $className;
            $this->validatorErrorCollectionParser->setErrorParserClass($this->getProperty('validator-error-parser-class'));
        }
        
        return $this->validatorErrorCollectionParser;
    }
    
}