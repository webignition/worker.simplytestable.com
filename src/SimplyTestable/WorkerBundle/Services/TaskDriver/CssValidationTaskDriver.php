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
        
        exec("java -jar ".$this->getProperty('jar-path')." -output ucn " .$task->getUrl(), $validationOutputLines);
        
        $this->getValidatorOutputParser()->setOutputLines($validationOutputLines);
        
        
        $validatorOutput = $this->getValidatorOutputParser()->getOutput();
        
        $this->response->setErrorCount(count($validatorOutput));
        
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