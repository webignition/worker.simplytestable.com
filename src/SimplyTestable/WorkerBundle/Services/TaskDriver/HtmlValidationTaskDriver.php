<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\JsonDocument\JsonDocument;

class HtmlValidationTaskDriver extends TaskDriver {
    
    public function execute(Task $task) {
        if (!$task->getType()->equals($this->getTaskTypeService()->getHtmlValidationTaskType())) {
            return false;
        }
        
        $resourceRequest = new \HttpRequest($task->getUrl(), HTTP_METH_GET);
        
        $this->getLogger()->info('HtmlValidationTaskDriver: validating ['.$task->getUrl().']');
        
        /* @var $webResource WebPage */
        $webResource = $this->getWebResourceService()->get($resourceRequest);
        if (!$webResource instanceof WebPage) {
            return false;
        }
        
        $this->getLogger()->info('HtmlValidationTaskDriver: fragment to validate ['.$webResource->getContent().']');
        $this->getLogger()->info('HtmlValidationTaskDriver: charset ['.$webResource->getCharacterEncoding().']'); 
        
        $validationRequest = new \HttpRequest($this->getProperty('validator-url'), HTTP_METH_POST);
        $validationRequest->setPostFields(array(
            'fragment' => $webResource->getContent(),
            'charset' => $webResource->getCharacterEncoding(),
            'output' => 'json'
        ));
        
        /* @var $validationResponse JsonDocument */
        $validationResponse = $this->getWebResourceService()->get($validationRequest);
        
        $this->getLogger()->info('HtmlValidationTaskDriver: validationResponse type ['.get_class($validationResponse).']'); 
        
        $outputObject = $this->getOutputOject($validationResponse->getContentObject());
        
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
    
    
}