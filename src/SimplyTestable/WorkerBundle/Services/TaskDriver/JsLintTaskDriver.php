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
            
            $output = $this->getJsLintOutputForUrl($scriptUrl);
            $jsLintOutput[(string)$scriptUrl] = $output['output'];
            $errorCount += $output['errorCount'];
        }
        
        $scriptValues = $this->getScriptValues();
        foreach ($scriptValues as $scriptValue) {            
            $nodeJsLintOutput = $this->validateJsContent($scriptValue);                         
            $jsLintOutput[md5($scriptValue)] = $this->nodeJsLintOutputToArray($nodeJsLintOutput);
            $errorCount += $nodeJsLintOutput->getEntryCount();
        }
        
        $this->response->setErrorCount($errorCount);
        
        foreach ($jsLintOutput as $sourcePath => $sourcePathOutput) {
            if (preg_match('/^\/tmp\/[a-z0-9]{32}:[0-9]+:[0-9]+\.[0-9]+$/', $sourcePathOutput['statusLine'])) {
                $jsLintOutput[$sourcePath]['statusLine'] = substr($sourcePathOutput['statusLine'], 0, strpos($sourcePathOutput['statusLine'], ':'));
            }
        }
        
        return json_encode($jsLintOutput);
    }
    
    
    private function getJsLintOutputForUrl($scriptUrl) {        
        $hash = md5($scriptUrl);
        
        if ($this->getTimeCachedTaskOutputService()->isStale($hash)) {
            $output = $this->getSourceJsLintOutputForUrl($scriptUrl);                        
            $this->getTimeCachedTaskOutputService()->set($hash, serialize($output['output']), $output['errorCount'], 0);
        }
        
        $timeCachedOutput = $this->getTimeCachedTaskOutputService()->find($hash);
        
        return array(
            'errorCount' => $timeCachedOutput->getErrorCount(),
            'output' => unserialize($timeCachedOutput->getOutput())
        );
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
        } catch (\webignition\Http\Client\Exception $httpClientException) {
            $this->errorCount++;

            return array(
                'errorCount' => 1,
                'output' => array(
                    'statusLine' => 'failed',
                    'errorReport' => array(
                        'reason' => 'httpClientException',
                        'statusCode' => $httpClientException->getCode()
                    )
                )
            );
        } catch (\webignition\Http\Client\CurlException $curlException) {
            $this->errorCount++;

            return array(
                'errorCount' => 1,
                'output' => array(
                    'statusLine' => 'failed',
                    'errorReport' => array(
                        'reason' => 'curlException',
                        'statusCode' => $curlException->getCode()
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
        $localPath = $this->getLocalJavaScriptResourcePathFromContent($js);
        
        file_put_contents($localPath, $js);
        
        $outputLines = array();
        
        $command = $this->getProperty('node-path') . " ".$this->getProperty('node-jslint-path')."/jslint.js --json ".$localPath;        
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
        return sys_get_temp_dir() . '/' . md5($content) . ':' . $this->task->getId() . ':' . microtime(true);
    }
    
    
    /**
     * 
     * @param type $scriptUrl
     * @return WebResource
     */
    private function getJavaScriptWebResourceFromUrl($url) {
        $this->getWebResourceService()->getHttpClient()->setUserAgent('SimplyTestable JS static analyser/0.1 (http://simplytestable.com/)');        
        $this->getWebResourceService()->getHttpClient()->redirectHandler()->clearRedirectCount();
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
                $absoluteUrlDeriver = new AbsoluteUrlDeriver($src, $thisUrl);
                
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