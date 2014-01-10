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
        $baseRequest = $this->getHttpClientService()->get()->get();        
        if ($this->task->hasParameter('http-auth-username') || $this->task->hasParameter('http-auth-password')) {
            $baseRequest->setAuth(
                $this->task->hasParameter('http-auth-username') ? $this->task->getParameter('http-auth-username') : '',
                $this->task->hasParameter('http-auth-password') ? $this->task->getParameter('http-auth-password') : '',
                'any'
            );
        }
        
        /* @var $cssValidatorWrapper \webignition\CssValidatorWrapper\Wrapper */
        $cssValidatorWrapper = $this->getProperty('css-validator-wrapper');                
        $cssValidatorWrapper->createConfiguration(array(
            'url-to-validate' => $this->webResource->getUrl(),
            'content-to-validate' => $this->webResource->getContent(),
            'css-validator-jar-path' => $this->getProperty('jar-path'),
            'vendor-extension-severity-level' => CssValidatorWrapperConfigurationVextLevel::isValid($this->task->getParameter('vendor-extensions'))
                ? $this->task->getParameter('vendor-extensions')
                : CssValidatorWrapperConfigurationVextLevel::LEVEL_WARN,
            'flags' => array(
                CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES
            ),
            'domains-to-ignore' => $this->task->hasParameter('domains-to-ignore')
                ? $this->task->getParameter('domains-to-ignore') 
                : array(),
            'base-request' => $baseRequest
        ));
        
        $cssValidatorWrapper->getConfiguration()->getWebResourceService()->getConfiguration()->enableRetryWithUrlEncodingDisabled();
        
        if ($this->task->isTrue('ignore-warnings')) {
            $this->getProperty('css-validator-wrapper')->getConfiguration()->setFlag(CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_WARNINGS);
        }
        
        
        /* @var $cssValidatorOutput \webignition\CssValidatorOutput\CssValidatorOutput */
        $cssValidatorOutput = $this->getProperty('css-validator-wrapper')->validate();
        
        if ($cssValidatorOutput->hasException()) {
            // Will only get unknown CSS validator exceptions here           
            $this->response->setHasFailed();
            $this->response->setErrorCount(1); 
            $this->response->setIsRetryable(false);
            return json_encode($this->getUnknownExceptionErrorOutput($this->task));
        }
        
        $this->response->setErrorCount($cssValidatorOutput->getErrorCount());
        $this->response->setWarningCount($cssValidatorOutput->getWarningCount());
        
        return $this->getSerializer()->serialize($this->prepareCssValidatorOutput($cssValidatorOutput), 'json');        
    }
    
    
    private function prepareCssValidatorOutput(\webignition\CssValidatorOutput\CssValidatorOutput $cssValidatorOutput) {
        $messages = $cssValidatorOutput->getMessages();
        
        foreach ($messages as $index => $message) {
            if ($message->isError()) {
                if ($this->isCssValidatorHttpError($message)) {
                    $message->setMessage('http-retrieval-' . $this->getCssValidatorHttpErrorStatusCode($message));
                }
                
                if ($this->isCssValidatorCurlError($message)) {                    
                    $message->setMessage('http-retrieval-curl-code-' . $this->getCssValidatorCurlErrorCode($message));
                }                
            }
        }
        
        return $messages;
    }
    
    
    /**
     * 
     * @param \webignition\CssValidatorOutput\Message\Error $error
     * @return boolean
     */
    private function isCssValidatorHttpError(\webignition\CssValidatorOutput\Message\Error $error) {
        $message = $error->getMessage();
        return substr($message, 0, strlen('http-error:')) === 'http-error:';
    }    

    /**
     * 
     * @param \webignition\CssValidatorOutput\Message\Error $error
     * @return boolean
     */
    private function isCssValidatorCurlError(\webignition\CssValidatorOutput\Message\Error $error) {
        $message = $error->getMessage();
        return substr($message, 0, strlen('curl-error:')) === 'curl-error:';
    }    
    
    
    /**
     * 
     * @param \webignition\CssValidatorOutput\Message\Error $error
     * @return boolean
     */
    private function getCssValidatorHttpErrorStatusCode(\webignition\CssValidatorOutput\Message\Error $error) {
        if (!$this->isCssValidatorHttpError($error)) {
            return null;
        }
        
        return (int)  str_replace('http-error:', '', $error->getMessage());
    }    
    
    
    /**
     * 
     * @param \webignition\CssValidatorOutput\Message\Error $error
     * @return boolean
     */
    private function getCssValidatorCurlErrorCode(\webignition\CssValidatorOutput\Message\Error $error) {
        if (!$this->isCssValidatorCurlError($error)) {
            return null;
        }
        
        return (int)  str_replace('curl-error:', '', $error->getMessage());
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