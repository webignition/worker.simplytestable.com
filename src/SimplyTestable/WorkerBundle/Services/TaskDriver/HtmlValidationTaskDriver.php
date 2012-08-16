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
        
        /* @var $webResource WebPage */
        $webResource = $this->getWebResourceService()->get($resourceRequest);
        if (!$webResource instanceof WebPage) {
            return false;
        }        
        
        $validationRequest = new \HttpRequest($this->getProperty('validator-url'), HTTP_METH_POST);
        $validationRequest->setPostFields(array(
            'fragment' => $webResource->getContent(),
            'charset' => $webResource->getCharacterEncoding(),
            'output' => 'json'
        ));
        
        /* @var $validationResponse JsonDocument */
        $validationResponse = $this->getWebResourceService()->get($validationRequest);
        
        $outputObject = $this->getOutputOject($validationResponse->getContentObject());
        
        return json_encode($outputObject);
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
            $outputObjectMessage->$requiredPropertyName = $validationMessageObject->$requiredPropertyName;
        }
        
        return $outputObjectMessage;
    }
    
    
}