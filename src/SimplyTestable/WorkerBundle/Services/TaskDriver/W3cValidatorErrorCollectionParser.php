<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

//use SimplyTestable\WorkerBundle\Entity\Task\Task;
//use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
//use webignition\WebResource\WebPage\WebPage;
//use webignition\WebResource\JsonDocument\JsonDocument;
use webignition\WebResource\WebPage\WebPage;

class W3cValidatorErrorCollectionParser {

    /**
     *
     * @var WebPage
     */
    private $webpage;
    
    
    /**
     *
     * @var string
     */
    private $errorParserClass = null;
    
    /**
     *
     * @var SimplyTestable\WorkerBundle\Services\TaskDriver\W3cValidatorErrorParser 
     */
    private $errorParser = null;
    
    
    /**
     *
     * @param string $errorParserClass 
     */
    public function setErrorParserClass($errorParserClass) {
        $this->errorParserClass = $errorParserClass;
    }
    
    
    /**
     *
     * @param \stdClass $validationResponseObject
     * @return \stdClass 
     */
    public function getOutputObject(WebPage $webpage) {
        $this->webpage = $webpage;
        
        $outputObject = new \stdClass();
        $outputObject->messages = array();

        $validatorErrors = array();

        $this->webpage->find('#fatal-errors li')->each(function ($index, \DOMElement $domElement) use (&$validatorErrors) {            
            $validatorErrorContent = '';
            
            /* @var $domElement DOMElement */
            $paragraphs = $domElement->getElementsByTagName('p');
            
            for ($paragraphIndex = 0; $paragraphIndex < $paragraphs->length; $paragraphIndex++) {
                $paragraph = $paragraphs->item($paragraphIndex);
                
                $document = new \DOMDocument();
                $cloned = $paragraph->cloneNode(true);
                $document->appendChild($document->importNode($cloned,true));
                
                $validatorErrorContent .= $document->saveHTML();
            }
            
            $validatorErrors[] = $validatorErrorContent;
        }); 
        
        foreach ($validatorErrors as $validatorError) {
            $this->getErrorParser()->setValidatorError($validatorError);
            $outputObject->messages[] = $this->getErrorParser()->getOutputObjectMessage();
        }
        
        return $outputObject;        
    }
    
    
    /**
     *
     * @return SimplyTestable\WorkerBundle\Services\TaskDriver\W3cValidatorErrorParser  
     */
    private function getErrorParser() {
        if (is_null($this->errorParser)) {
            $this->errorParser = new $this->errorParserClass;
        }
        
        return $this->errorParser;
    }
    
}