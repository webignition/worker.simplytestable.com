<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\JsonDocument\JsonDocument;
use SimplyTestable\WorkerBundle\Services\TaskDriver\W3cValidatorErrorParser;

class HtmlValidationTaskDriver extends TaskDriver {    
    
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\TaskDriver\W3cValidatorErrorParser 
     */
    private $validatorErrorCollectionParser = null;
    
    
    /**
     *
     * @param Task $task
     * @return string 
     */
    public function execute(Task $task) {
        if (!$task->getType()->equals($this->getTaskTypeService()->getHtmlValidationTaskType())) {
            return false;
        }
        
        $resourceRequest = new \HttpRequest($task->getUrl(), HTTP_METH_GET);
        
        /* @var $webResource WebPage */
        $webResource = $this->getWebResourceService()->get($resourceRequest);
        if (!$webResource instanceof WebPage) {
            return false;
        }
        
        $characterEncoding = ($webResource->getIsDocumentCharacterEncodingValid()) ? $webResource->getCharacterEncoding() : self::DEFAULT_CHARACTER_ENCODING;                
        
        $validationRequest = new \HttpRequest($this->getProperty('validator-url'), HTTP_METH_POST);
        
        $requestPostFields = array(
            'fragment' => $webResource->getContent(),
            'output' => 'json'
        );
        
        if (!is_null($characterEncoding)) {
            $requestPostFields['charset'] = $characterEncoding;
        }
        
        $validationRequest->setPostFields($requestPostFields);

        /* @var $validationResponse JsonDocument */
        $validationResponse = $this->getWebResourceService()->get($validationRequest);
        
        if ($validationResponse->getContentType()->getTypeSubtypeString() == 'text/html') {
            // HTML response, the validator failed to validate
            $outputObject = $this->getW3cValidatorErrorCollectionParser()->getOutputObject($validationResponse);
            $this->response->setHasFailed();
            $this->response->setIsRetryable(false);
        } else {
            // Regular JSON output
            $outputObject = $this->getOutputOject($validationResponse->getContentObject());
            $this->response->setHasSucceeded();
        }        
        
        return json_encode($outputObject);
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
     * @return SimplyTestable\WorkerBundle\Services\TaskDriver\W3cValidatorErrorParser
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