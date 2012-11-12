<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

class CssValidationOutputParser {  
    
    /**
     *
     * @var array
     */
    private $outputLines;
    
    
    /**
     *
     * @var \DOMDocument
     */
    private $outputDom;
    
    
    /**
     * 
     * @param array $outputLines
     */
    public function setOutputLines($outputLines) {
        $this->outputLines = $outputLines;
    }           
    
    
    /**
     * 
     * @return array
     */
    public function getOutput() {
        if (!is_array($this->outputLines)) {
            return array();
        }
        
        $outputMessages = $this->getOutputDom()->getElementsByTagName('message');
        
        $output = array();
        
        foreach ($outputMessages as $messageNode) {
            $contextNode = $messageNode->getElementsByTagName('context')->item(0);
            
            $error = new \stdClass();
            
            $error->context = $contextNode->nodeValue;
            $error->lineNumber = (int)$contextNode->getAttribute('line');
            $error->message = trim($messageNode->getElementsByTagName('title')->item(0)->nodeValue);
            $error->ref = $messageNode->getAttribute('ref');
            
            $output[] = $error;
        }
        
        return $output;
    }
    
    
    private function getOutputDom() {
        if (is_null($this->outputDom)) {
            $this->outputDom = new \DOMDocument();
            $this->outputDom->loadXML(implode('', array_slice($this->outputLines, 1)));
        }
        
        return $this->outputDom;
    }
}