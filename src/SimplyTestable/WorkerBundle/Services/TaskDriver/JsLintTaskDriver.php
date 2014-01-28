<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;
use webignition\Url\Url;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\WebResource\Exception\Exception as WebResourceException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\NodeJslint\Wrapper\Configuration\Flag\JsLint as JsLintFlag;
use webignition\NodeJslint\Wrapper\Configuration\Option\JsLint as JsLintOption;

class JsLintTaskDriver extends WebResourceTaskDriver {  
    
     const JSLINT_PARAMETER_NAME_PREFIX = 'jslint-option-';    
     const MAXIMUM_FRAGMENT_LENGTH = 256;    
    
    /**
     *
     * @var string[]
     */
    private $localResourcePaths = array();
    
    
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
        $this->configureNodeJslintWrapper();
        
        $jsLintOutput = array();
        $scriptUrls = $this->getScriptUrls();
        $scriptValues = $this->getScriptValues();
        
        foreach ($scriptValues as $scriptValue) {
            $localPath = $this->getLocalJavaScriptResourcePathFromContent($scriptValue);
            file_put_contents($localPath, $scriptValue);
            
            $localUrl = new Url();
            $localUrl->setScheme('file');
            $localUrl->setPath($localPath);
            
            $scriptUrls[] = $localUrl;          
        }
        
        $errorCount = 0;
        
        foreach ($scriptUrls as $scriptUrl) {            
            if ($this->isScriptDomainIgnored($scriptUrl)) {
                continue;
            }
            
            try {
                $nodeJsLintOutput = $this->validateJsFile((string)$scriptUrl);
                foreach ($nodeJsLintOutput->getEntries() as $entry) {
                    /* @var $entry \webignition\NodeJslintOutput\Entry\Entry */
                    if (strlen($entry->getEvidence()) > self::MAXIMUM_FRAGMENT_LENGTH) {
                        $entry->setEvidence(substr($entry->getEvidence(), 0, self::MAXIMUM_FRAGMENT_LENGTH));
                    }            
                }
            
                $jsLintOutput[(string)$scriptUrl] = $this->nodeJsLintOutputToArray($nodeJsLintOutput);
                $errorCount += $this->getNodeJsErrorCount($nodeJsLintOutput);
            } catch (\webignition\NodeJslintOutput\Exception $nodeJslintOutputException) {
                $this->getLogger()->err('JSLintTaskDriver::jslint error: [at '.$scriptUrl.']['.$nodeJslintOutputException->getMessage().']');
                throw $nodeJslintOutputException;
            } catch (InvalidContentTypeException $invalidContentTypeException) {                                        
                $jsLintOutput[(string)$scriptUrl] = array(
                    'statusLine' => 'failed',
                    'errorReport' => array(
                        'reason' => 'InvalidContentTypeException',
                        'contentType' => $invalidContentTypeException->getResponseContentType()->getTypeSubtypeString()
                    )
                );
                
                $errorCount++;
            } catch (WebResourceException $webResourceException) {                
                $jsLintOutput[(string)$scriptUrl] = array(
                    'statusLine' => 'failed',
                    'errorReport' => array(
                        'reason' => 'webResourceException',
                        'statusCode' => $webResourceException->getCode()
                    )
                );
                
                $errorCount++;
            } catch (\Guzzle\Http\Exception\CurlException $curlException) {
                $jsLintOutput[(string)$scriptUrl] = array(
                    'statusLine' => 'failed',
                    'errorReport' => array(
                        'reason' => 'curlException',
                        'statusCode' => $curlException->getErrorNo()
                    )
                );
                
                $errorCount++;
            }
        }
              
        foreach ($scriptValues as $scriptValue) {
            @unlink($this->getLocalJavaScriptResourcePathFromContent($scriptValue));
        }
        
        $this->response->setErrorCount($errorCount);
        
        foreach ($jsLintOutput as $sourcePath => $sourcePathOutput) {            
            if (preg_match('/^\/tmp\/[a-z0-9]{32}:[0-9]+:[0-9]+\.[0-9]+\.js$/', $sourcePathOutput['statusLine'])) {
                $jsLintOutput[$sourcePath]['statusLine'] = substr($sourcePathOutput['statusLine'], 0, strpos($sourcePathOutput['statusLine'], ':'));
            }
        }
        
        foreach ($jsLintOutput as $sourcePath => $sourcePathOutput) {            
            if (preg_match('/^file:\/tmp\/[a-z0-9]{32}:[0-9]+:[0-9]+\.[0-9]+\.js$/', $sourcePath)) {
                $newSourcePath = preg_replace('/^file:\/tmp\//', '', $sourcePath);
                $firstColonPosition = strpos($newSourcePath, ':');                
                $jsLintOutput[substr($newSourcePath, 0, $firstColonPosition)] = $sourcePathOutput;
                unset($jsLintOutput[$sourcePath]);             
            }
        }        
        
        return json_encode($jsLintOutput);
    }
    
    
    /**
     * 
     * @param \webignition\NodeJslintOutput\NodeJslintOutput $nodeJsLintOutput
     * @return int
     */
    private function getNodeJsErrorCount(\webignition\NodeJslintOutput\NodeJslintOutput $nodeJsLintOutput) {
        $errorCount = $nodeJsLintOutput->getEntryCount();        
        if ($nodeJsLintOutput->wasStopped()) {
            $errorCount--;
        }
        
        if ($nodeJsLintOutput->hasTooManyErrors()) {
            $errorCount--;
        }
        
        if ($errorCount < 0) {
            $errorCount = 0;
        }
        
        return $errorCount;
    }    
    
    private function entryToArray(\webignition\NodeJslintOutput\Entry\Entry $entry) {
        $output = array(
            'headerLine' => array(
                'errorNumber' => 1,
                'errorMessage' => $entry->getReason()
            ),
            'fragmentLine' => array(
                'fragment' => $entry->getEvidence(),
                'lineNumber' => $entry->getLineNumber(),
                'columnNumber' => $entry->getColumnNumber()
            )
        );

        return $output;
    }
    
    private function nodeJsLintOutputToArray(\webignition\NodeJslintOutput\NodeJslintOutput $nodeJsLintOutput) {
        $output = array();
        $output['statusLine'] = $nodeJsLintOutput->getStatusLine();
        $output['entries'] = array();
        
        foreach ($nodeJsLintOutput->getEntries() as $entry) {
            /* @var $entry \webignition\NodeJslintOutput\Entry\Entry */
            $output['entries'][] = $this->entryToArray($entry);
        }
        
        return $output;        
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
    
    
    /**
     * 
     * @param type $url
     * @return \webignition\NodeJslintOutput\NodeJslintOutput
     */
    private function validateJsFile($url) {
        /* @var $nodeJslintWrapper \webignition\NodeJslint\Wrapper\Wrapper */
        $nodeJslintWrapper = $this->getProperty('node-jslint-wrapper');
        $nodeJslintWrapper->getConfiguration()->setUrlToLint($url);
        
        return $nodeJslintWrapper->validate();
    }

    
    private function configureNodeJslintWrapper() {
        $baseRequest = $this->getHttpClientService()->get()->get();        
        if ($this->task->hasParameter('http-auth-username') || $this->task->hasParameter('http-auth-password')) {
            $baseRequest->setAuth(
                $this->task->hasParameter('http-auth-username') ? $this->task->getParameter('http-auth-username') : '',
                $this->task->hasParameter('http-auth-password') ? $this->task->getParameter('http-auth-password') : '',
                'any'
            );
        }
        
        /* @var $nodeJslintWrapper \webignition\NodeJslint\Wrapper\Wrapper */
        $nodeJslintWrapper = $this->getProperty('node-jslint-wrapper');
        $nodeJslintWrapper->getLocalProxy()->getConfiguration()->setBaseRequest($baseRequest);        
        $nodeJslintWrapper->getLocalProxy()->getWebResourceService()->getConfiguration()->enableRetryWithUrlEncodingDisabled();
        
        $nodeJslintWrapper->getConfiguration()->setNodeJslintPath($this->getProperty('node-jslint-path'));
        $nodeJslintWrapper->getConfiguration()->setNodePath($this->getProperty('node-path'));        
        
        $parametersObject = $this->task->getParametersObject();        
        if (!is_null($parametersObject)) {
            foreach ($parametersObject as $key => $value) {
                if (!$this->isJslintParameter($key)) {
                    continue;
                }

                $jsLintKey = str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key);

                if ($this->isJslintBooleanParameter($jsLintKey)) {
                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                        $nodeJslintWrapper->getConfiguration()->enableFlag($jsLintKey);
                    } else {
                        $nodeJslintWrapper->getConfiguration()->disableFlag($jsLintKey);
                    }                
                } elseif ($this->isJsLintSingleOccurrenceOptionParameter($jsLintKey)) {                
                    $nodeJslintWrapper->getConfiguration()->setOption($jsLintKey, $value);
                } elseif ($this->isJslintCollectionOptionParameter($jsLintKey)) {
                    if (is_array($value)) {
                        $value = $value[0];
                    }

                    $nodeJslintWrapper->getConfiguration()->setOption($jsLintKey, explode(' ', $value));                   
                }
            }             
        }     
    }
    
    
    /**
     * 
     * @param string $key
     * @return boolean
     */
    private function isJslintParameter($key) {        
        return substr($key, 0, strlen(self::JSLINT_PARAMETER_NAME_PREFIX)) == self::JSLINT_PARAMETER_NAME_PREFIX;
    }
    
    
    /**
     * 
     * @param string $jsLintKey
     * @return boolean
     */
    private function isJslintBooleanParameter($jsLintKey) {        
        return in_array($jsLintKey, JsLintFlag::getList());
    }
    
    /**
     * 
     * @param string $jsLintKey
     * @return boolean
     */
    private function isJslintOptionParameter($jsLintKey) {        
        return in_array($jsLintKey, JsLintOption::getList());
    }
            
    /**
     * 
     * @param string $jsLintKey
     * @return boolean
     */
    private function isJsLintSingleOccurrenceOptionParameter($jsLintKey) {        
        $singleOccurrenceOptions = array(
            JsLintOption::INDENT,
            JsLintOption::MAXERR,
            JsLintOption::MAXLEN,
        );
        
        return $this->isJslintOptionParameter($jsLintKey) && in_array($jsLintKey, $singleOccurrenceOptions);
    }             
    
    /**
     * 
     * @param string $jsLintKey
     * @return boolean
     */
    private function isJslintCollectionOptionParameter($jsLintKey) {
        $singleOccurrenceOptions = array(
            JsLintOption::PREDEF
        );
        
        return $this->isJslintOptionParameter($jsLintKey) && in_array($jsLintKey, $singleOccurrenceOptions);
    }
    
    
    /**
     * 
     * @param string $content
     * @return string
     */
    private function getLocalJavaScriptResourcePathFromContent($content) {
        $key = $this->getLocalJavaScriptResourcePathKey($content);
        if (!isset($this->localResourcePaths[$key])) {
            $this->localResourcePaths[$key] = sys_get_temp_dir() . '/' . $key . ':' . microtime(true) . '.js';
        }
        
        return $this->localResourcePaths[$key];
    }
    

    /**
     * 
     * @param string $content
     * @return string
     */    
    private function getLocalJavaScriptResourcePathKey($content) {
        return md5($content) . ':' . $this->task->getId();
    }
    
    
    /**
     * 
     * @return array
     */
    private function getScriptUrls() {
        if ($this->webResource instanceof WebPage) {
            $webPage = clone $this->webResource;
        } else {
            $webPage = new WebPage();
            $webPage->setContent($this->webResource->getContent());            
        }
        
        $scriptUrls = array();
        
        $thisUrl = new Url($this->task->getUrl());
        
        $webPage->find('script')->each(function ($index, \DOMElement $domElement) use (&$scriptUrls, $thisUrl) {                       
            $src = trim($domElement->getAttribute('src'));           
            if ($src != '') {
                $absoluteUrlDeriver = new AbsoluteUrlDeriver($src, $thisUrl);
                $absoluteScriptUrl = $absoluteUrlDeriver->getAbsoluteUrl();
                
                if (!in_array($absoluteScriptUrl, $scriptUrls)) {
                    $scriptUrls[] = $absoluteScriptUrl;
                }
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