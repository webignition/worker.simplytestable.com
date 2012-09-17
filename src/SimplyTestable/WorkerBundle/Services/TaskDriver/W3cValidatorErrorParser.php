<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

//use SimplyTestable\WorkerBundle\Entity\Task\Task;
//use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
//use webignition\WebResource\WebPage\WebPage;
//use webignition\WebResource\JsonDocument\JsonDocument;
use webignition\WebResource\WebPage\WebPage;

class W3cValidatorErrorParser {
    
    /**
     *
     * @var string
     */
    private $validatorError = null;
    
    
    /**
     *
     * @var string
     */
    private $errorType = null;
    
    
    /**
     *
     * @param string $validatorError 
     */
    public function setValidatorError($validatorError) {
        $this->validatorError = $validatorError;
    }    

    
    /**
     *
     * @return \stdClass 
     */
    public function getOutputObjectMessage() {        
        $outputObjectMessage = new \stdClass;
        $outputObjectMessage->lastLine = $this->getLastLine();
        $outputObjectMessage->lastColumn = $this->getLastColumn();
        $outputObjectMessage->message = $this->validatorError;
        $outputObjectMessage->messageId = $this->getErrorType();
        $outputObjectMessage->type = 'error';
        
        return $outputObjectMessage;
    }
    
    /**
     *
     * @return string 
     */
    private function getErrorType() {
        if (is_null($this->errorType)) {
            $this->errorType = $this->findErrorType();
        }
        
        return $this->errorType;
    }
    
    
    /**
     *
     * @return string 
     */
    private function findErrorType() {
        if (substr_count($this->validatorError, "it contained one or more bytes that I cannot interpret as")) {
            return 'character-encoding';
        }
        
        return 'unknown';
    }
    
    
    /**
     *
     * @return int
     */
    private function getLastLine() {
        if ($this->getErrorType() == 'character-encoding') {
            $lineNumberMatches = array();
            preg_match('/<strong>[0-9]+<\/strong>/', $this->validatorError, $lineNumberMatches);            
            return (int)str_replace(array('<strong>', '</strong>'), '', $lineNumberMatches[0]);
        }
        
        return -1;
    }
    
    
    /**
     *
     * @return int
     */
    private function getLastColumn() {
        return -1;
    }
    
}