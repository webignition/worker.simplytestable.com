<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;
use webignition\CssValidatorOutput\Parser as CssValidatorOutputParser;

class CssValidationTaskDriver extends TaskDriver {
    
    /**
     * 
     * @param \SimplyTestable\WorkerBundle\Entity\Task\Task $task
     * @return string
     */
    public function execute(Task $task) {                
        if (!$this->isCorrectTaskType($task)) {
            return false;
        }
        
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
        
        $validationOutputLines = array();
        $command = "java -jar ".$this->getProperty('jar-path')." ".  implode(' ', $commandOptionsStrings)." \"" .$task->getUrl()."\" 2>&1";        
        exec($command, $validationOutputLines);
        
        $cssValidatorOutputParser = new CssValidatorOutputParser();
        $cssValidatorOutputParser->setRawOutput(implode("\n", $validationOutputLines));
        
        if ($task->hasParameter('domains-to-ignore')) {
            $cssValidatorOutputParser->setRefDomainsToIgnore($task->getParameter('domains-to-ignore'));
        }
        
        if ($task->isTrue('ignore-warnings')) {
            $cssValidatorOutputParser->setIgnoreWarnings(true);
        }
        
        if ($task->getParameter('vendor-extensions') == 'ignore') {    
            $cssValidatorOutputParser->setIgnoreVendorExtensionIssues(true);
        }
        
        if ($task->getParameter('vendor-extensions') == 'warn' && $task->isTrue('ignore-warnings')) {
            $cssValidatorOutputParser->setIgnoreVendorExtensionIssues(true);
        }        
        
        $cssValidatorOutput = $cssValidatorOutputParser->getOutput();
        
        if ($cssValidatorOutput->getIsUnknownMimeTypeError()) {
            $this->response->setHasBeenSkipped();
            $this->response->setErrorCount(0);
            return true;            
        }
        
        if ($cssValidatorOutput->getIsUnknownExceptionError()) {
            $this->response->setHasFailed();
            $this->response->setErrorCount(1);            
            return json_encode($this->getUnknownExceptionErrorOutput($task));
        }
        
        $this->response->setErrorCount($cssValidatorOutput->getErrorCount());
        $this->response->setWarningCount($cssValidatorOutput->getWarningCount());
        
        return $this->getSerializer()->serialize($cssValidatorOutput->getMessages(), 'json');
    } 
    
    
    /**
     *
     * @return \stdClass 
     */
    protected function getUnknownExceptionErrorOutput(Task $task) {        
        $outputObjectMessage = new \stdClass();
        $outputObjectMessage->message = 'Unknown error';
        $outputObjectMessage->class = 'css-validation-exception-unknown';
        $outputObjectMessage->type = 'error';
        $outputObjectMessage->context = '';
        $outputObjectMessage->ref = $task->getUrl();
        $outputObjectMessage->line_number = 0;
        
        return array($outputObjectMessage);        
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
    
}