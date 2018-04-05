<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkUrlFinderConfiguration;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;

class UrlDiscoveryTaskDriver extends WebResourceTaskDriver
{
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';

    /**
     * @var string[]
     */
    private $equivalentSchemes = array(
        'http',
        'https'
    );

    /**
     * @param HttpClientService $httpClientService
     * @param WebResourceRetriever $webResourceService
     * @param StateService $stateService
     */
    public function __construct(
        HttpClientService $httpClientService,
        WebResourceRetriever $webResourceService,
        StateService $stateService
    ) {
        $this->setHttpClientService($httpClientService);
        $this->setWebResourceService($webResourceService);
        $this->setStateService($stateService);
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
        $configuration = new LinkUrlFinderConfiguration([
            LinkUrlFinderConfiguration::CONFIG_KEY_SOURCE => $this->webResource,
            LinkUrlFinderConfiguration::CONFIG_KEY_SOURCE_URL => $this->webResource->getUrl(),
            LinkUrlFinderConfiguration::CONFIG_KEY_ELEMENT_SCOPE => 'a',
            LinkUrlFinderConfiguration::CONFIG_KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON => true,
        ]);

        if ($this->task->hasParameter('scope')) {
            $configuration->setUrlScope($this->task->getParameter('scope'));
        }

        $finder = new HtmlDocumentLinkUrlFinder();
        $finder->setConfiguration($configuration);
        $finder->getUrlScopeComparer()->addEquivalentSchemes($this->equivalentSchemes);

        return json_encode($finder->getUniqueUrls());
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
}
