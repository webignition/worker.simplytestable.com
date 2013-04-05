<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;
use webignition\CssValidatorOutput\Parser as CssValidatorOutputParser;

class CssValidationTaskDriver extends WebResourceTaskDriver {
    
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
     * @return string
     */
    protected function hasNotSucceedHandler() {
        $this->response->setErrorCount(1);
        return json_encode($this->getWebResourceExceptionOutput());        
    }

    protected function isBlankWebResourceHandler() {
        $this->response->setHasBeenSkipped();
        $this->response->setErrorCount(0);
        return true;        
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

    protected function isNotCorrectWebResourceTypeHandler() {
        $this->response->setHasBeenSkipped();
        $this->response->setErrorCount(0);
        return true;
    }

    protected function performValidation() { 
        $commandOptions = array(
            'output' => 'ucn'
        );
        
        if ($this->task->getParameter('vendor-extensions') == 'warn') {
            $commandOptions['vextwarning'] = 'true';
        }
        
        if ($this->task->getParameter('vendor-extensions') == 'error') {
            $commandOptions['vextwarning'] = 'false';
        }        
        
        $commandOptionsStrings = '';
        foreach ($commandOptions as $key => $value) {
            $commandOptionsStrings[] = '-'.$key.' '.$value;
        }
        
        $validationOutputLines = array();
        $command = "java -jar ".$this->getProperty('jar-path')." ".  implode(' ', $commandOptionsStrings)." \"" .$this->webResource->getUrl()."\" 2>&1";        
        exec($command, $validationOutputLines);
        
        $cssValidatorOutputParser = new CssValidatorOutputParser();
        $cssValidatorOutputParser->setIgnoreFalseBackgroundImageDataUrlMessages(true);
        $cssValidatorOutputParser->setRawOutput(implode("\n", $validationOutputLines));
        
        if ($this->task->hasParameter('domains-to-ignore')) {
            $cssValidatorOutputParser->setRefDomainsToIgnore($this->task->getParameter('domains-to-ignore'));
        }
        
        if ($this->task->isTrue('ignore-warnings')) {
            $cssValidatorOutputParser->setIgnoreWarnings(true);
        }
        
        if ($this->task->getParameter('vendor-extensions') == 'ignore') {    
            $cssValidatorOutputParser->setIgnoreVendorExtensionIssues(true);
        }
        
        if ($this->task->getParameter('vendor-extensions') == 'warn' && $this->task->isTrue('ignore-warnings')) {
            $cssValidatorOutputParser->setIgnoreVendorExtensionIssues(true);
        }        
        
        $cssValidatorOutput = $cssValidatorOutputParser->getOutput();

        if ($cssValidatorOutput->getIsUnknownMimeTypeError()) {
            $this->response->setHasBeenSkipped();
            $this->response->setErrorCount(0);
            $this->response->setIsRetryable(false);
            return true;            
        }
        
        if ($cssValidatorOutput->getIsUnknownExceptionError()) {
            $this->response->setHasFailed();
            $this->response->setErrorCount(1); 
            $this->response->setIsRetryable(false);
            return json_encode($this->getUnknownExceptionErrorOutput($this->task));
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
}