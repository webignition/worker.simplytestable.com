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
     * @var boolean
     */
    private $ignoreWarnings = false;
    
    
    /**
     *
     * @var int
     */
    private $errorCount = 0;
    
    
    /**
     *
     * @var int
     */
    private $warningCount = 0;
    
    
    /**
     *
     * @var boolean
     */
    private $ignoreVendorExtensions = false;
    
    
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
        $this->errorCount = 0;
        $this->warningCount = 0;
        
        if (!is_array($this->outputLines)) {
            return array();
        }
        
        $outputMessages = $this->getOutputDom()->getElementsByTagName('message');
        
        $output = array();
        
        foreach ($outputMessages as $messageNode) {            
            $type = $messageNode->getAttribute('type');
            
            if ($this->getIgnoreWarnings() == true && $type == 'warning') {
                continue;
            }
            
            $ref = trim($messageNode->getAttribute('ref'));
            
            if ($this->isRefToBeIgnored($ref)) {
                continue;
            } 
            
            $contextNode = $messageNode->getElementsByTagName('context')->item(0);
                  
            $message = new \stdClass();
            $message->message = trim($messageNode->getElementsByTagName('title')->item(0)->nodeValue);
            
            if ($this->isVendorExtensionMessage($message->message) && $this->getIgnoreVendorExtensions() === true) {
                continue;
            }

            $message->context = $contextNode->nodeValue;
            $message->lineNumber = (int)$contextNode->getAttribute('line');            
            $message->ref = $ref;
            $message->type = $type;

            $output[] = $message;

            if ($type == 'error') {
                $this->errorCount++;
            }

            if ($type == 'warning') {
                $this->warningCount++;
            }
        }
        
        return $output;
    }
    
    
    /**
     * 
     * @param string $message
     * @return boolean
     */
    private function isVendorExtensionMessage($message) {       
        $patterns = array(
            '/is an unknown vendor extension/',
            '/^Property \-[a-z\-]+ doesn\&#39;t exist/',
            '/^Unknown pseudo\-element or pseudo\-class [:]{1,2}\-[a-z\-]+/',
            '/-webkit\-focus\-ring\-color is not a outline\-color value/'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message) > 0) {
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * 
     * @return int
     */
    public function getErrorCount() {
        return $this->errorCount;
    }
    
    
    /**
     * 
     * @return int
     */
    public function getWarningCount() {
        return $this->warningCount;
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
    
    
    /**
     * 
     * @param boolean $ignoreWarnings
     */
    public function setIgnoreWarnings($ignoreWarnings) {
        $this->ignoreWarnings = $ignoreWarnings;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function getIgnoreWarnings() {
        return $this->ignoreWarnings;
    }
    
    
    /**
     * 
     * @param boolean $ignoreVendorExtensions
     */
    public function setIgnoreVendorExtensions($ignoreVendorExtensions) {
        $this->ignoreVendorExtensions = $ignoreVendorExtensions;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function getIgnoreVendorExtensions() {
        return $this->ignoreVendorExtensions;
    }
} 