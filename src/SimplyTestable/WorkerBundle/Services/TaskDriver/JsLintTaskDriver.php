<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use webignition\InternetMediaType\InternetMediaType;
use webignition\NodeJslint\Wrapper\Wrapper as NodeJsLintWrapper;
use webignition\NodeJslintOutput\NodeJslintOutput;
use webignition\WebResource\Service\Configuration;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\WebPage\WebPage;
use webignition\Url\Url;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\WebResource\Exception\Exception as WebResourceException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\NodeJslint\Wrapper\Configuration\Flag\JsLint as JsLintFlag;
use webignition\NodeJslint\Wrapper\Configuration\Option\JsLint as JsLintOption;
use webignition\NodeJslintOutput\Exception as NodeJslintOutputException;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;
use webignition\NodeJslintOutput\Entry\Entry as NodeJslintOutputEntry;
use webignition\NodeJslint\Wrapper\Configuration\Configuration as NodeJslintWrapperConfiguration;

class JsLintTaskDriver extends WebResourceTaskDriver
{
    const JSLINT_PARAMETER_NAME_PREFIX = 'jslint-option-';
    const MAXIMUM_FRAGMENT_LENGTH = 256;

    /**
     * @var string[]
     */
    private $disallowedScriptUrlSchemes = [
        'resource',
        'file'
    ];

    /**
     * @var string[]
     */
    private $localResourcePaths = array();

    /**
     * @var NodeJsLintWrapper
     */
    private $nodeJsLintWrapper;

    /**
     * @var string
     */
    private $nodePath;

    /**
     * @var string
     */
    private $nodeJsLintPath;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param HttpClientService $httpClientService
     * @param WebResourceRetriever $webResourceService
     * @param NodeJsLintWrapper $nodeJsLintWrapper
     * @param StateService $stateService
     * @param LoggerInterface $logger
     * @param string $nodePath
     * @param string $nodeJsLintPath
     */
    public function __construct(
        HttpClientService $httpClientService,
        WebResourceRetriever $webResourceService,
        NodeJsLintWrapper $nodeJsLintWrapper,
        StateService $stateService,
        LoggerInterface $logger,
        $nodePath,
        $nodeJsLintPath
    ) {
        $this->setHttpClientService($httpClientService);
        $this->setWebResourceService($webResourceService);
        $this->setNodeJsLintWrapper($nodeJsLintWrapper);
        $this->setStateService($stateService);
        $this->logger = $logger;
        $this->nodePath = $nodePath;
        $this->nodeJsLintPath = $nodeJsLintPath;
    }

    /**
     * @param NodeJsLintWrapper $wrapper
     */
    public function setNodeJsLintWrapper(NodeJsLintWrapper $wrapper)
    {
        $this->nodeJsLintWrapper = $wrapper;
    }

    /**
     * @return InternetMediaType
     */
    protected function getOutputContentType()
    {
        $contentType = new InternetMediaType();
        $contentType->setType('application');
        $contentType->setSubtype('json');

        return $contentType;
    }

    /**
     * {@inheritdoc}
     */
    protected function hasNotSucceededHandler()
    {
        $this->response->setErrorCount(1);

        return json_encode($this->getWebResourceExceptionOutput());
    }

    /**
     * {@inheritdoc}
     */
    protected function isNotCorrectWebResourceTypeHandler()
    {
        $this->response->setHasBeenSkipped();
        $this->response->setIsRetryable(false);
        $this->response->setErrorCount(0);
    }

    /**
     * {@inheritdoc}
     */
    protected function isBlankWebResourceHandler()
    {
        $this->response->setHasBeenSkipped();
        $this->response->setErrorCount(0);
    }

    /**
     * {@inheritdoc}
     */
    protected function performValidation()
    {
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

        $this->getHttpClientService()->setUserAgent('ST Link JS Static Analysis Task Driver (http://bit.ly/RlhKCL)');
        $this->getHttpClientService()->setCookies($this->task->getParameter('cookies'));
        $this->getHttpClientService()->setBasicHttpAuthorization(
            $this->task->getParameter('http-auth-username'),
            $this->task->getParameter('http-auth-password')
        );

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
            } catch (NodeJslintOutputException $nodeJslintOutputException) {
                $this->logger->error(sprintf(
                    'JSLintTaskDriver::jslint error: [at %s][%s]',
                    $scriptUrl,
                    $nodeJslintOutputException->getMessage()
                ));

                $this->getHttpClientService()->clearCookies();
                $this->getHttpClientService()->clearBasicHttpAuthorization();

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
            } catch (ConnectException $connectException) {
                $curlException = GuzzleCurlExceptionFactory::fromConnectException($connectException);

                $jsLintOutput[(string)$scriptUrl] = array(
                    'statusLine' => 'failed',
                    'errorReport' => array(
                        'reason' => 'curlException',
                        'statusCode' => $curlException->getCurlCode()
                    )
                );

                $errorCount++;
            }
        }

        $this->getHttpClientService()->resetUserAgent();
        $this->getHttpClientService()->clearCookies();
        $this->getHttpClientService()->clearBasicHttpAuthorization();

        foreach ($scriptValues as $scriptValue) {
            @unlink($this->getLocalJavaScriptResourcePathFromContent($scriptValue));
        }

        $this->response->setErrorCount($errorCount);

        foreach ($jsLintOutput as $sourcePath => $sourcePathOutput) {
            if (preg_match('/^file:(\/|\/\/\/)tmp\/[a-z0-9]{32}:[0-9]+:[0-9]+\.[0-9]+\.js$/', $sourcePath)) {
                $newSourcePath = preg_replace('/^file:(\/|\/\/\/)tmp\//', '', $sourcePath);
                $firstColonPosition = strpos($newSourcePath, ':');
                $jsLintOutput[substr($newSourcePath, 0, $firstColonPosition)] = $sourcePathOutput;
                unset($jsLintOutput[$sourcePath]);
            }
        }

        return json_encode($jsLintOutput);
    }

    /**
     * @param NodeJslintOutput $nodeJsLintOutput
     *
     * @return int
     */
    private function getNodeJsErrorCount(NodeJslintOutput $nodeJsLintOutput)
    {
        $errorCount = $nodeJsLintOutput->getEntryCount();
        if ($nodeJsLintOutput->wasStopped()) {
            $errorCount--;
        }

        if ($nodeJsLintOutput->hasTooManyErrors()) {
            $errorCount--;
        }

        return max(0, $errorCount);
    }

    /**
     * @param NodeJslintOutputEntry $entry
     *
     * @return array
     */
    private function entryToArray(NodeJslintOutputEntry $entry)
    {
        $output = [
            'headerLine' => [
                'errorNumber' => 1,
                'errorMessage' => $entry->getReason()
            ],
            'fragmentLine' => [
                'fragment' => $entry->getEvidence(),
                'lineNumber' => $entry->getLineNumber(),
                'columnNumber' => $entry->getColumnNumber()
            ]
        ];

        return $output;
    }

    /**
     * @param NodeJslintOutput $nodeJsLintOutput
     *
     * @return array
     */
    private function nodeJsLintOutputToArray(NodeJslintOutput $nodeJsLintOutput)
    {
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
     * @param Url $scriptUrl
     *
     * @return boolean
     */
    private function isScriptDomainIgnored(Url $scriptUrl)
    {
        if (!$this->task->hasParameter('domains-to-ignore')) {
            return false;
        }

        $domainsToIgnore = $this->task->getParameter('domains-to-ignore');

        return is_array($domainsToIgnore) && in_array($scriptUrl->getHost(), $domainsToIgnore);
    }

    /**
     * @param string $url
     *
     * @return NodeJslintOutput
     */
    private function validateJsFile($url)
    {
        $this->nodeJsLintWrapper->getConfiguration()->setUrlToLint($url);
        $response = $this->nodeJsLintWrapper->validate();

        return $response;
    }

    private function configureNodeJslintWrapper()
    {
        $this->nodeJsLintWrapper
            ->getLocalProxy()
            ->getConfiguration()
            ->setHttpClient($this->getHttpClientService()->get());

        $nodeJsLintWrapperWebResourceService = $this->nodeJsLintWrapper
            ->getLocalProxy()
            ->getWebResourceService();

        $newNodeJsLintWrapperWebResourceServiceConfiguration = $nodeJsLintWrapperWebResourceService
            ->getConfiguration()
            ->createFromCurrent([
                Configuration::CONFIG_RETRY_WITH_URL_ENCODING_DISABLED => true,
            ]);

        $nodeJsLintWrapperWebResourceService
            ->setConfiguration($newNodeJsLintWrapperWebResourceServiceConfiguration);

        $configurationValues = array_merge([
            NodeJslintWrapperConfiguration::CONFIG_KEY_NODE_JSLINT_PATH => $this->nodeJsLintPath,
            NodeJslintWrapperConfiguration::CONFIG_KEY_NODE_PATH => $this->nodePath,
        ], $this->getNodeJsLintConfigurationFlagsAndOptionsFromParameters($this->task->getParametersArray()));

        $this->nodeJsLintWrapper->createConfiguration($configurationValues);
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    private function getNodeJsLintConfigurationFlagsAndOptionsFromParameters($parameters = [])
    {
        if (empty($parameters)) {
            $parameters = [];
        }

        $flags = [];
        $options = [];

        foreach ($parameters as $key => $value) {
            if (!$this->isJslintParameter($key)) {
                continue;
            }

            $jsLintKey = str_replace(self::JSLINT_PARAMETER_NAME_PREFIX, '', $key);

            if ($this->isJslintBooleanParameter($jsLintKey)) {
                $flags[$jsLintKey] = (bool)$value;
            } elseif ($this->isJsLintSingleOccurrenceOptionParameter($jsLintKey)) {
                $options[$jsLintKey] = $value;
            } elseif ($this->isJslintCollectionOptionParameter($jsLintKey)) {
                if (is_array($value)) {
                    $value = $value[0];
                }

                $options[$jsLintKey] = explode(' ', $value);
            }
        }

        return [
            NodeJslintWrapperConfiguration::CONFIG_KEY_FLAGS => $flags,
            NodeJslintWrapperConfiguration::CONFIG_KEY_OPTIONS => $options,
        ];
    }

    /**
     * @param string $key
     *
     * @return boolean
     */
    private function isJslintParameter($key)
    {
        return substr($key, 0, strlen(self::JSLINT_PARAMETER_NAME_PREFIX)) == self::JSLINT_PARAMETER_NAME_PREFIX;
    }

    /**
     * @param string $jsLintKey
     *
     * @return boolean
     */
    private function isJslintBooleanParameter($jsLintKey)
    {
        return in_array($jsLintKey, JsLintFlag::getList());
    }

    /**
     * @param string $jsLintKey
     *
     * @return boolean
     */
    private function isJslintOptionParameter($jsLintKey)
    {
        return in_array($jsLintKey, JsLintOption::getList());
    }

    /**
     * @param string $jsLintKey
     *
     * @return boolean
     */
    private function isJsLintSingleOccurrenceOptionParameter($jsLintKey)
    {
        $singleOccurrenceOptions = array(
            JsLintOption::INDENT,
            JsLintOption::MAXERR,
            JsLintOption::MAXLEN,
        );

        return $this->isJslintOptionParameter($jsLintKey) && in_array($jsLintKey, $singleOccurrenceOptions);
    }

    /**
     * @param string $jsLintKey
     *
     * @return boolean
     */
    private function isJslintCollectionOptionParameter($jsLintKey)
    {
        $singleOccurrenceOptions = array(
            JsLintOption::PREDEF
        );

        return $this->isJslintOptionParameter($jsLintKey) && in_array($jsLintKey, $singleOccurrenceOptions);
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function getLocalJavaScriptResourcePathFromContent($content)
    {
        $key = $this->getLocalJavaScriptResourcePathKey($content);
        if (!isset($this->localResourcePaths[$key])) {
            $this->localResourcePaths[$key] = sys_get_temp_dir() . '/' . $key . ':' . microtime(true) . '.js';
        }

        return $this->localResourcePaths[$key];
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function getLocalJavaScriptResourcePathKey($content)
    {
        return md5($content) . ':' . $this->task->getId();
    }

    /**
     * @return array
     */
    private function getScriptUrls()
    {
        /* @var WebPage $webPage */
        $webPage = $this->webResource;

        $scriptUrls = array();
        $thisUrl = new Url($webPage->getUrl());

        $webPage->find('script')->each(function ($index, \DOMElement $domElement) use (&$scriptUrls, $thisUrl) {
            $src = trim($domElement->getAttribute('src'));
            if ($src != '') {
                $absoluteUrlDeriver = new AbsoluteUrlDeriver($src, $thisUrl);
                $absoluteScriptUrl = $absoluteUrlDeriver->getAbsoluteUrl();

                $isAllowedUrlScheme = !in_array($absoluteScriptUrl->getScheme(), $this->disallowedScriptUrlSchemes);

                if ($isAllowedUrlScheme && !in_array($absoluteScriptUrl, $scriptUrls)) {
                    $scriptUrls[] = $absoluteScriptUrl;
                }
            }
        });

        return $scriptUrls;
    }

    /**
     * @return array
     */
    private function getScriptValues()
    {
        /* @var WebPage $webPage */
        $webPage = $this->webResource;

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
