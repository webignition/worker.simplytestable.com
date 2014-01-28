<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;
use webignition\Url\Url;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\WebResource\WebResource;
use webignition\WebResource\Exception\Exception as WebResourceException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\NodeJslint\Wrapper\Configuration\Flag\JsLint as JsLintFlag;
use webignition\NodeJslint\Wrapper\Configuration\Option\JsLint as JsLintOption;

class JsLintTaskDriver extends WebResourceTaskDriver {  
    
     const JSLINT_PARAMETER_NAME_PREFIX = 'jslint-option-';    
     const MAXIMUM_FRAGMENT_LENGTH = 256;
    
    /**
     *
     * @var string
     */
    private $jsLintCommandOptions = null;
    
    
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
        
        $this->jsLintCommandOptions = null;
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
                'errorCount' => $this->getNodeJsErrorCount($nodeJsLintOutput),
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
    
    /**
     * ^
     * @throws \InvalidArgumentException
     * @throws \webignition\NodeJslintOutput\Exception
     * @return \webignition\NodeJslintOutput\NodeJslintOutput
     */    
    
    
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
        
//        var_dump($nodeJslintWrapper->getLocalProxy()->getConfiguration()->getBaseRequest()->getClient()->getEventDispatcher()->getListeners('request.before_send'));        
//        //var_dump($this->getHttpClientService()->get()->getEventDispatcher()->getListeners('request.before_send'));
//        exit();        
        
        $nodeJslintWrapper->getLocalProxy()->getWebResourceService()->getConfiguration()->enableRetryWithUrlEncodingDisabled();
        
        $nodeJslintWrapper->getConfiguration()->setNodeJslintPath($this->getProperty('node-jslint-path'));
        $nodeJslintWrapper->getConfiguration()->setNodePath($this->getProperty('node-path'));        
        
        $parametersObject = $this->task->getParametersObject();        
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
//        
//        var_dump($nodeJslintWrapper->getConfiguration());
//        exit();
        

//            $parametersObject = $this->task->getParametersObject();            
//            foreach ($parametersObject as $key => $value) {
//                if ($this->isJslintParameter($key)) {
//                    if ($this->isJslintBooleanParameter($key)) {
//                        $jsLintCommandOptions .= ' --' . str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key) . '=';
//                        $jsLintCommandOptions .= filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
//                    } else {                        
//                        if (str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key) === 'predef') {
//                            if (is_array($value)) {
//                                $value = $value[0];
//                            }
//                            
//                            $values = explode(' ', $value);                            
//                            foreach ($values as $predefValue) {
//                                $jsLintCommandOptions .= ' --' . str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key) . '=' . $predefValue;
//                            }                        
//                        } else {
//                            $jsLintCommandOptions .= ' --' . str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key) . '=' . $value;
//                        }                        
//                    }  
//                }                                              
//            } 
        
        
        
        
//        $nodeJslintWrapper->getConfiguration()->setFlag($name);        
//        $nodeJslintWrapper->getConfiguration()->setOption($name, $value);
        
        
        
        
        
        
        //$wrapper = new \webignition\NodeJslint\Wrapper\Wrapper();
        //$wrapper->getC
//        
//        var_dump($this->getJsLintCommandOptions());
//        exit();        
    }
    
    
    
    private function validateJsContent($js) {    
/**
    var flags = [
            'anon', 'bitwise', 'browser', 'cap', 'continue', 'css',
            'debug', 'devel', 'eqeq', 'es5', 'evil', 'forin', 'fragment',
            'newcap', 'node', 'nomen', 'on', 'passfail', 'plusplus',
            'properties', 'regexp', 'rhino', 'undef', 'unparam',
            'sloppy', 'stupid', 'sub', 'vars', 'white', 'widget', 'windows',
            'json', 'color', 'terse'
        ],
        commandOpts = {
            'indent' : Number,
            'maxerr' : Number,
            'maxlen' : Number,
            'predef' : [String, Array]
        };
 */        
        
//        var_dump($this->getJsLintCommandOptions());
//        exit();
        
        
        
        $localPath = $this->getLocalJavaScriptResourcePathFromContent($js);
        
        file_put_contents($localPath, $js);
        
        $outputLines = array();
        
        $command = $this->getProperty('node-path') . " ".$this->getProperty('node-jslint-path')."/jslint.js --json ". $this->getJsLintCommandOptions() . " " .  $localPath . " 2>&1";        
        exec($command, $outputLines);
        
        $output = implode("\n", $outputLines);    

        $outputParser = new \webignition\NodeJslintOutput\Parser();        
//        if (!$outputParser->parse($output)) {
//            throw new \SimplyTestable\WorkerBundle\Exception\JsLintTaskDriverException($output, 1);
//        }
        
        $nodeJsLintOutput = $outputParser->parse($output);

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
                            if (is_array($value)) {
                                $value = $value[0];
                            }
                            
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
     * @param string $url
     * @return WebResource
     */    
    private function getJavaScriptWebResourceFromUrl($url) {                    
        $this->getHttpClientService()->get()->setUserAgent('ST Link JS Static Analysis Task Driver (http://bit.ly/RlhKCL)');
        $request = $this->getHttpClientService()->getRequest((string)$url, array());
        $webResource = $this->getWebResourceService()->get($request);        
        $this->getHttpClientService()->get()->setUserAgent(null);             

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