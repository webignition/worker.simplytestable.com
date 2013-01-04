<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;
use webignition\Url\Url;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\WebResource\WebResource;
use webignition\NodeJslintOutput\Parser;

class JsLintTaskDriver extends WebResourceTaskDriver {  
    
    
    /**
     * 
     * @param \SimplyTestable\WorkerBundle\Entity\Task\Task $task
     * @return boolean
     */
    protected function isCorrectTaskType(Task $task) {
        return $task->getType()->equals($this->getTaskTypeService()->getJavaScriptStatisAnalysisTaskType());
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
     * @return string
     */
    protected function hasNotSucceedHandler() {
        $this->response->setErrorCount(1);
        return json_encode($this->getWebResourceExceptionOutput());        
    }
    
    protected function isNotCorrectWebResourceTypeHandler() {
        $this->response->setHasBeenSkipped();
        $this->response->setErrorCount(0);
        return true;
    }
    
    
    protected function isBlankWebResourceHandler() {
        $this->response->setHasBeenSkipped();
        $this->response->setErrorCount(0);
        return true;        
    }

    protected function performValidation() {
        $jsLintOutput = array();
        $scriptUrls = $this->getScriptUrls();              
        
        $errorCount = 0;
        
        foreach ($scriptUrls as $scriptUrl) {
            if ($this->isScriptDomainIgnored($scriptUrl)) {
                continue;
            }
            
            try {
                $nodeJsLintOutput = $this->validateScriptFromUrl($scriptUrl);
                
                $errorCount += $nodeJsLintOutput->getEntryCount();
                
                $jsLintOutput[(string)$scriptUrl] = $nodeJsLintOutput->__toArray();            
            } catch (WebResourceException $webResourceException) {
                var_dump("WebResourceException");
                exit();
                
//                $this->response->setHasFailed();
//                $this->response->setIsRetryable(false);
//
//                $this->webResourceException = $webResourceException;           
            } catch (\webignition\Http\Client\Exception $httpClientException) {
                var_dump("HttpClientException");
                exit();                
//                $this->response->setHasFailed();
//                $this->response->setIsRetryable(false);
//
//                $this->httpClientException = $httpClientException;
            } catch (\webignition\Http\Client\CurlException $curlException) {
                var_dump("CurlException");
                exit();                
//                $this->response->setHasFailed();
//
//                if ($curlException->isTimeoutException()) {
//                    $this->response->setIsRetryable(false);
//                }
//
//                if ($curlException->isDnsLookupFailureException()) {
//                    $this->response->setIsRetryable(false);
//                }            
//
//                if ($curlException->isInvalidUrlException()) {
//                    $this->response->setIsRetryable(false);
//                }              
//
//                $this->curlException = $curlException;
            }
        }
        
        $scriptValues = $this->getScriptValues();
        foreach ($scriptValues as $scriptValue) {
            $nodeJsLintOutput = $this->validateJsContent($scriptValue); 
            
            $errorCount += $nodeJsLintOutput->getEntryCount();
            
            $jsLintOutput[md5($scriptValue)] = $nodeJsLintOutput->__toArray();  
        }
        
        $this->response->setErrorCount($errorCount);
        
        return json_encode($jsLintOutput);
    }
    
    
    /**
     * 
     * @param \webignition\Url\Url $scriptUrl
     * @return boolean
     */
    private function isScriptDomainIgnored(Url $scriptUrl) {
        if (!$this->task->hasParameter('domains-to-ignore')) {
            return false;
        }
        
        $domainsToIgnore = $this->task->getParameter('domains-to-ignore');
        if (!is_array($domainsToIgnore)) {
            return false;
        }        
        
        return in_array($scriptUrl->getHost(), $domainsToIgnore);
    }
    
    
    private function validateScriptFromUrl(Url $url) {        
        $webResource = $this->getJavaScriptWebResourceFromUrl($url);        
        return $this->validateJsContent($webResource->getContent());              
    }
    
    
    private function validateJsContent($js) {           
        $localPath = $this->getLocalJavaScriptResourcePathFromContent($js);
        
        file_put_contents($localPath, $js);
        
        $outputLines = array();
        
        $command = $this->getProperty('node-path') . " ".$this->getProperty('node-jslint-path')."/jslint.js ".$localPath;        
        exec($command, $outputLines); 
        
        $output = implode("\n", $outputLines);
        
        $outputParser = new \webignition\NodeJslintOutput\Parser();
        $outputParser->parse($output);
        
        $nodeJsLintOutput = $outputParser->getNodeJsLintOutput();
        
        unlink($localPath);
        
        return $nodeJsLintOutput;         
    }
    
    
    /**
     * 
     * @param string $content
     * @return string
     */
    private function getLocalJavaScriptResourcePathFromContent($content) {
        return sys_get_temp_dir() . '/' . md5($content);
    }    
    
    
    /**
     * 
     * @param type $scriptUrl
     * @return WebResource
     */
    private function getJavaScriptWebResourceFromUrl($url) {
        $this->getWebResourceService()->getHttpClient()->setUserAgent('SimplyTestable JS static analyser/0.1 (http://simplytestable.com/)');        
        $request = new \HttpRequest((string)$url, HTTP_METH_GET);
        $webResource = $this->getWebResourceService()->get($request);
        $this->getWebResourceService()->getHttpClient()->clearUserAgent();        
        
        return $webResource;      
    }
    
    
    /**
     * 
     * @return array
     */
    private function getScriptUrls() {        
        $webPage = new WebPage();
        $webPage->setContent($this->webResource->getContent()); 
        
        $scriptUrls = array();
        
        $thisUrl = new Url($this->task->getUrl());
        
        $webPage->find('script')->each(function ($index, \DOMElement $domElement) use (&$scriptUrls, $thisUrl) {            
            $src = trim($domElement->getAttribute('src'));
            if ($src != '') {
                $absoluteUrlDeriver = new AbsoluteUrlDeriver(new Url($src), $thisUrl);
                
                $scriptUrls[] = $absoluteUrlDeriver->getAbsoluteUrl();
            }
        });
        
        return $scriptUrls;      
    }
    
    
    /**
     * 
     * @return array
     */
    private function getScriptValues() {
        $webPage = new WebPage();
        $webPage->setContent($this->webResource->getContent());
        
        $scriptValues = array();
        
        $webPage->find('script')->each(function ($index, \DOMElement $domElement) use (&$scriptValues) {
            $nodeValue = trim($domElement->nodeValue);
            if ($nodeValue != '') {
                $scriptValues[] = $nodeValue;
            }
        }); 
        
        return $scriptValues;
    }  
    
    
    
}