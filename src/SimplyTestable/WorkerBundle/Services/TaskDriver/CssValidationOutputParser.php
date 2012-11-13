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
     * @var array
     */
    private $refDomainsToIgnore = array();
    
    
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
            $type = $messageNode->getAttribute('type');
            if ($type == 'error') {
                $contextNode = $messageNode->getElementsByTagName('context')->item(0);
                $ref = trim($messageNode->getAttribute('ref'));

                if (!$this->isRefToBeIgnored($ref)) {
                    $error = new \stdClass();

                    $error->context = $contextNode->nodeValue;
                    $error->lineNumber = (int)$contextNode->getAttribute('line');
                    $error->message = trim($messageNode->getElementsByTagName('title')->item(0)->nodeValue);
                    $error->ref = $ref;                

                    $output[] = $error;
                }                
            }
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
    
    
    /**
     * 
     * @param array $refDomainsToIgnore
     */
    public function setRefDomainsToIgnore($refDomainsToIgnore) {
        $this->refDomainsToIgnore = $refDomainsToIgnore;                
    }
    
    
    /**
     * 
     * @return array
     */
    public function getRefDomainsToIgnore() {
        return $this->refDomainsToIgnore;
    }
    
    
    /**
     * 
     * @param string $ref
     * @return boolean
     */
    private function isRefToBeIgnored($ref) {               
        if ($ref == '') {
            return false;
        }
        
        $refUrl = new \webignition\Url\Url($ref);
        
        foreach ($this->getRefDomainsToIgnore() as $refDomainToIgnore) {                       
            if ($refUrl->getHost()->isEquivalentTo(new \webignition\Url\Host\Host($refDomainToIgnore))) {
                return true;
            }
        }
        
        return false;
    }
} 