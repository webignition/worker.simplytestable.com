<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;
use webignition\CssValidatorWrapper\Configuration\Flags as CssValidatorWrapperConfigurationFlags;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel as CssValidatorWrapperConfigurationVextLevel;

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
        $this->getProperty('css-validator-wrapper')->createConfiguration(array(
            'url-to-validate' => $this->webResource->getUrl(),
            'css-validator-jar-path' => $this->getProperty('jar-path'),
            'vendor-extension-severity-level' => CssValidatorWrapperConfigurationVextLevel::isValid($this->task->getParameter('vendor-extensions'))
                ? $this->task->getParameter('vendor-extensions')
                : CssValidatorWrapperConfigurationVextLevel::LEVEL_WARN,
            'flags' => array(
                CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_FALSE_BACKGROUND_IMAGE_DATA_URL_MESSAGES
            ),
            'domains-to-ignore' => $this->task->hasParameter('domains-to-ignore')
                ? $this->task->getParameter('domains-to-ignore') 
                : array()
        ));
        
        if ($this->task->isTrue('ignore-warnings')) {
            $this->getProperty('css-validator-wrapper')->getConfiguration()->setFlag(CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_WARNINGS);
        }
        
        $cssValidatorOutput = $this->getProperty('css-validator-wrapper')->validate();
        
        if ($cssValidatorOutput->getIsUnknownMimeTypeError()) {
            $this->response->setHasBeenSkipped();
            $this->response->setErrorCount(0);
            $this->response->setIsRetryable(false);
            return true;            
        } 
        
        if ($cssValidatorOutput->getIsSSlExceptionErrorOutput()) {
            $this->response->setHasFailed();
            $this->response->setErrorCount(1); 
            $this->response->setIsRetryable(false);
            return json_encode($this->getSslExceptionErrorOutput($this->task));
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
    
    
    /**
     *
     * @return \stdClass 
     */
    protected function getSslExceptionErrorOutput(Task $task) {        
        $outputObjectMessage = new \stdClass();
        $outputObjectMessage->message = 'SSL Error';
        $outputObjectMessage->class = 'css-validation-ss-error';
        $outputObjectMessage->type = 'error';
        $outputObjectMessage->context = '';
        $outputObjectMessage->ref = $task->getUrl();
        $outputObjectMessage->line_number = 0;
        
        return array($outputObjectMessage);        
    }   
}