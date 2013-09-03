<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;
use webignition\Url\Url;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\WebResource\WebResource;
use webignition\NodeJslintOutput\Parser;
use SimplyTestable\WorkerBundle\Exception\WebResourceException;

class JsLintTaskDriver extends WebResourceTaskDriver {  
    
     const JSLINT_PARAMETER_NAME_PREFIX = 'jslint-option-';
    
    
    /**
     *
     * @var string
     */
    private $jsLintCommandOptions = null;
    
    /**
     * jslint-option-passfail
     * jslint-option-bitwise
     * jslint-option-continue
     * jslint-option-debug
     * jslint-option-evil
     * jslint-option-eqeq
     * jslint-option-es5
     * jslint-option-forin
     * jslint-option-newcap
     * jslint-option-nomen
     * jslint-option-plusplus
     * jslint-option-regexp
     * jslint-option-undef
     * jslint-option-unparam
     * jslint-option-sloppy
     * jslint-option-stupid
     * jslint-option-sub
     * jslint-option-vars
     * jslint-option-white
     * jslint-option-anon
     * jslint-option-browser
     * jslint-option-devel
     * jslint-option-windows
     * 
     * jslint-option-maxerr":"50","jslint-option-indent":"4","jslint-option-maxlen":"256","jslint-option-predef":[""]}
     * 
     * @var array
     */
    private $jsLintBooleanOptions = array(
        'passfail',
        'bitwise',
        'continue',
        'debug',
        'evil',
        'eqeq',
        'es5',
        'forin',
        'newcap',
        'nomen',
        'plusplus',
        'regexp',
        'undef',
        'unparam',
        'sloppy',
        'stupid',
        'sub',
        'vars',
        'white',
        'anon',
        'browser',
        'devel',
        'windows',       
    );
    
    
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
        $this->jsLintCommandOptions = null;
        $jsLintOutput = array();
        $scriptUrls = $this->getScriptUrls();              
        
        $errorCount = 0;
        
        foreach ($scriptUrls as $scriptUrl) {
            if ($this->isScriptDomainIgnored($scriptUrl)) {
                continue;
            }
            
            $output = $this->getJsLintOutputForUrl($scriptUrl);
            $jsLintOutput[(string)$scriptUrl] = $output['output'];
            $errorCount += $output['errorCount'];
        }
        
        $scriptValues = $this->getScriptValues();
        foreach ($scriptValues as $scriptValue) {              
            $nodeJsLintOutput = $this->validateJsContent($scriptValue);                         
            $jsLintOutput[md5($scriptValue)] = $this->nodeJsLintOutputToArray($nodeJsLintOutput);            
            $errorCount += $this->getNodeJsErrorCount($nodeJsLintOutput);
        }
        
        $this->response->setErrorCount($errorCount);
        
        foreach ($jsLintOutput as $sourcePath => $sourcePathOutput) {
            if (preg_match('/^\/tmp\/[a-z0-9]{32}:[0-9]+:[0-9]+\.[0-9]+$/', $sourcePathOutput['statusLine'])) {
                $jsLintOutput[$sourcePath]['statusLine'] = substr($sourcePathOutput['statusLine'], 0, strpos($sourcePathOutput['statusLine'], ':'));
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
    
    
    private function getJsLintOutputForUrl($scriptUrl) {        
        return $this->getSourceJsLintOutputForUrl($scriptUrl);
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
    
    
    
    private function getSourceJsLintOutputForUrl($scriptUrl) {        
        try {            
            $nodeJsLintOutput = $this->validateScriptFromUrl($scriptUrl);
            
            foreach ($nodeJsLintOutput->getEntries() as $entry) {
                /* @var $entry \webignition\NodeJslintOutput\Entry\Entry */
                if (strlen($entry->getEvidence()) > 256) {
                    $entry->setEvidence(substr($entry->getEvidence(), 0, 256));
                }            
            }
            
            return array(
                'errorCount' => $nodeJsLintOutput->getEntryCount(),
                'output' => $this->nodeJsLintOutputToArray($nodeJsLintOutput)
            );           
        } catch (WebResourceException $webResourceException) {
            $this->errorCount++;
            
            return array(
                'errorCount' => 1,
                'output' => array(
                    'statusLine' => 'failed',
                    'errorReport' => array(
                        'reason' => 'webResourceException',
                        'statusCode' => $webResourceException->getCode()
                    )
                )
            );          
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {
            $this->errorCount++;

            return array(
                'errorCount' => 1,
                'output' => array(
                    'statusLine' => 'failed',
                    'errorReport' => array(
                        'reason' => 'curlException',
                        'statusCode' => $curlException->getErrorNo()
                    )
                )
            );
        } catch (\Guzzle\Http\Exception\ClientErrorResponseException $clientErrorResponseException) {
            $this->errorCount++;
            
            return array(
                'errorCount' => 1,
                'output' => array(
                    'statusLine' => 'failed',
                    'errorReport' => array(
                        'reason' => 'webResourceException',
                        'statusCode' => $clientErrorResponseException->getResponse()->getStatusCode()
                    )
                )
            );            
        } catch (\Guzzle\Http\Exception\ServerErrorResponseException $serverErrorResponseException) {
            return array(
                'errorCount' => 1,
                'output' => array(
                    'statusLine' => 'failed',
                    'errorReport' => array(
                        'reason' => 'webResourceException',
                        'statusCode' => $serverErrorResponseException->getResponse()->getStatusCode()
                    )
                )
            );              
        }        
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
        $this->getJsLintCommandOptions();
        
        $localPath = $this->getLocalJavaScriptResourcePathFromContent($js);
        
        file_put_contents($localPath, $js);
        
        $outputLines = array();
        
        $command = $this->getProperty('node-path') . " ".$this->getProperty('node-jslint-path')."/jslint.js --json ". $this->getJsLintCommandOptions() . " " .  $localPath;        
        exec($command, $outputLines);
        
        $output = implode("\n", $outputLines);       
        
        $outputParser = new \webignition\NodeJslintOutput\Parser();        
        $outputParser->parse($output);
        
        $nodeJsLintOutput = $outputParser->getNodeJsLintOutput();
        
        unlink($localPath);
        
        return $nodeJsLintOutput;         
    }
    
    private function getJsLintCommandOptions() {
        if (is_null($this->jsLintCommandOptions) && $this->task->hasParameters()) {
            $jsLintCommandOptions = '';
            
            $parametersObject = $this->task->getParametersObject();            
            foreach ($parametersObject as $key => $value) {
                if ($this->isJslintParameter($key)) {
                    if ($this->isJslintBooleanParameter($key)) {
                        $jsLintCommandOptions .= ' --' . str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key) . '=';
                        $jsLintCommandOptions .= filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                    } else {
                        if (str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key) === 'predef') {
                            $values = explode(' ', $value);                            
                            foreach ($values as $predefValue) {
                                $jsLintCommandOptions .= ' --' . str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key) . '=' . $predefValue;
                            }                        
                        } else {
                            $jsLintCommandOptions .= ' --' . str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key) . '=' . $value;
                        }                        
                    }  
                }                                              
            }     
            
            $this->jsLintCommandOptions = $jsLintCommandOptions;
        }
        
        return $this->jsLintCommandOptions;
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
     * @param string $key
     * @return boolean
     */
    private function isJslintBooleanParameter($key) {
        return in_array(str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key), $this->jsLintBooleanOptions);
    }
    
    
    /**
     * 
     * @param string $content
     * @return string
     */
    private function getLocalJavaScriptResourcePathFromContent($content) {
        return sys_get_temp_dir() . '/' . md5($content) . ':' . $this->task->getId() . ':' . microtime(true);
    }
    
    /**
     * 
     * @param string $url
     * @return WebResource
     */    
    private function getJavaScriptWebResourceFromUrl($url) {            
        $this->getWebResourceService()->getHttpClientService()->get()->setUserAgent('SimplyTestable JS static analyser/0.1 (http://simplytestable.com/)');        
        $request = $this->getWebResourceService()->getHttpClientService()->getRequest((string)$url, array());
        $webResource = $this->getWebResourceService()->get($request);        
        $this->getWebResourceService()->getHttpClientService()->get()->setUserAgent(null);             

        return $webResource;       
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