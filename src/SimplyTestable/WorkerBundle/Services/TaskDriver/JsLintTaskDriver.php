<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use Psr\Log\LoggerInterface;
use QueryPath\Exception as QueryPathException;
use SimplyTestable\WorkerBundle\Services\FooHttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\NodeJslint\Wrapper\Wrapper as NodeJsLintWrapper;
use webignition\NodeJslintOutput\Entry\ParserException as OutputEntryParserException;
use webignition\NodeJslintOutput\NodeJslintOutput;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\WebPage\WebPage;
use webignition\Url\Url;
use webignition\AbsoluteUrlDeriver\AbsoluteUrlDeriver;
use webignition\NodeJslintOutput\Exception as NodeJslintOutputException;
use webignition\NodeJslintOutput\Entry\Entry as NodeJslintOutputEntry;

class JsLintTaskDriver extends AbstractWebPageTaskDriver
{
    const JSLINT_PARAMETER_NAME_PREFIX = 'jslint-option-';
    const MAXIMUM_FRAGMENT_LENGTH = 256;

    const USER_AGENT = 'ST Link JS Static Analysis Task Driver (http://bit.ly/RlhKCL)';

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var NodeJsLintWrapperConfigurationFactory
     */
    private $nodeJsLintWrapperConfigurationFactory;

    /**
     * @param StateService $stateService
     * @param FooHttpClientService $fooHttpClientService
     * @param WebResourceRetriever $webResourceRetriever
     * @param LoggerInterface $logger
     * @param NodeJsLintWrapper $nodeJsLintWrapper
     * @param NodeJsLintWrapperConfigurationFactory $nodeJsLintWrapperConfigurationFactory
     */
    public function __construct(
        StateService $stateService,
        FooHttpClientService $fooHttpClientService,
        WebResourceRetriever $webResourceRetriever,
        LoggerInterface $logger,
        NodeJsLintWrapper $nodeJsLintWrapper,
        NodeJsLintWrapperConfigurationFactory $nodeJsLintWrapperConfigurationFactory
    ) {
        parent::__construct($stateService, $fooHttpClientService, $webResourceRetriever);

        $this->nodeJsLintWrapper = $nodeJsLintWrapper;
        $this->logger = $logger;
        $this->nodeJsLintWrapperConfigurationFactory = $nodeJsLintWrapperConfigurationFactory;

        $this->nodeJsLintWrapper->setHttpClient($this->fooHttpClientService->getHttpClient());
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

        return json_encode($this->getHttpExceptionOutput());
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
     *
     * @throws InternetMediaTypeParseException
     * @throws NodeJslintOutputException
     * @throws OutputEntryParserException
     * @throws TransportException
     * @throws QueryPathException
     */
    protected function performValidation()
    {
        $wrapperConfiguration = $this->nodeJsLintWrapperConfigurationFactory->create($this->task);
        $this->nodeJsLintWrapper->setConfiguration($wrapperConfiguration);

        $jsLintOutput = [];
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
                $nodeJsLintOutput = $this->nodeJsLintWrapper->validate($scriptUrl);

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

                throw $nodeJslintOutputException;
            } catch (HttpException $httpException) {
                $jsLintOutput[(string)$scriptUrl] = [
                    'statusLine' => 'failed',
                    'errorReport' => [
                        'reason' => 'webResourceException',
                        'statusCode' => $httpException->getCode(),
                    ]
                ];

                $errorCount++;
            } catch (InvalidResponseContentTypeException $invalidResponseContentTypeException) {
                $jsLintOutput[(string)$scriptUrl] = [
                    'statusLine' => 'failed',
                    'errorReport' => [
                        'reason' => 'InvalidContentTypeException',
                        'contentType' => $invalidResponseContentTypeException->getContentType()->getTypeSubtypeString(),
                    ]
                ];

                $errorCount++;
            } catch (TransportException $transportException) {
                if ($transportException->isCurlException()) {
                    $jsLintOutput[(string)$scriptUrl] = [
                        'statusLine' => 'failed',
                        'errorReport' => [
                            'reason' => 'curlException',
                            'statusCode' => $transportException->getCode(),
                        ]
                    ];

                    $errorCount++;
                } elseif ($transportException->isTooManyRedirectsException()) {
                    $jsLintOutput[(string)$scriptUrl] = [
                        'statusLine' => 'failed',
                        'errorReport' => [
                            'reason' => 'webResourceException',
                            'statusCode' => 301,
                        ]
                    ];

                    $errorCount++;
                } else {
                    throw $transportException;
                }
            }
        }

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
     *
     * @throws QueryPathException
     */
    private function getScriptUrls()
    {
        /* @var WebPage $webPage */
        $webPage = $this->webResource;

        $scriptUrls = array();
        $thisUrl = new Url((string)$webPage->getUri());

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
     *
     * @throws QueryPathException
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
