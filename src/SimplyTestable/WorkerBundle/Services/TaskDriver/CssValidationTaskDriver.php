<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;

class CssValidationTaskDriver extends TaskDriver {    
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\TaskDriver\CssValidationOutputParser
     */
    private $validatorOutputParser = null; 
    
    
    /**
     * 
     * @param \SimplyTestable\WorkerBundle\Entity\Task\Task $task
     * @return string
     */
    public function execute(Task $task) {                
        if (!$this->isCorrectTaskType($task)) {
            return false;
        }

        $validationOutputLines = array();
        
        $commandOptions = array(
            'output' => 'ucn'
        );
        
        if ($task->getParameter('vendor-extensions') == 'warn') {
            $commandOptions['vextwarning'] = 'true';
        }
        
        if ($task->getParameter('vendor-extensions') == 'error') {
            $commandOptions['vextwarning'] = 'false';
        }        
        
        $commandOptionsStrings = '';
        foreach ($commandOptions as $key => $value) {
            $commandOptionsStrings[] = '-'.$key.' '.$value;
        }  
        
        $command = "java -jar ".$this->getProperty('jar-path')." ".  implode(' ', $commandOptionsStrings)." " .$task->getUrl();        
        exec($command, $validationOutputLines);        
        
        $this->getValidatorOutputParser()->setOutputLines($validationOutputLines);
        
        if ($task->hasParameter('ref-domains-to-ignore')) {
            $this->getValidatorOutputParser()->setRefDomainsToIgnore($task->getParameter('ref-domains-to-ignore'));
        }
        
        if ($task->isTrue('ignore-warnings')) {
            $this->getValidatorOutputParser()->setIgnoreWarnings(true);
        }
        
        if ($task->getParameter('vendor-extensions') == 'ignore') {
            $this->getValidatorOutputParser()->setIgnoreVendorExtensions(true);
        }
        
        $validatorOutput = $this->getValidatorOutputParser()->getOutput();
        
        $this->response->setErrorCount($this->getValidatorOutputParser()->getErrorCount());
        $this->response->setWarningCount($this->getValidatorOutputParser()->getWarningCount());
        
        return json_encode($validatorOutput);
    }    
    
    
    /**
     * 
     * @param \SimplyTestable\WorkerBundle\Entity\Task\Task $task
     * @return boolean
     */
    protected function isCorrectTaskType(Task $task) {        
        return $task->getType()->equals($this->getTaskTypeService()->getCssValidationTaskType());
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
     * @return SimplyTestable\WorkerBundle\Services\TaskDriver\CssValidationOutputParser   
     */
    private function getValidatorOutputParser() {
        if (is_null($this->validatorOutputParser)) {
            $className = $this->getProperty('validator-output-parser');
            $this->validatorOutputParser = new $className;        
        }
        
        return $this->validatorOutputParser;
    }
    
    
    
}