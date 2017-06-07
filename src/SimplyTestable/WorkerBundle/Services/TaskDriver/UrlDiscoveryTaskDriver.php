<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Service\Service as WebResourceService;
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
     * @param WebResourceService $webResourceService
     * @param StateService $stateService
     */
    public function __construct(
        HttpClientService $httpClientService,
        WebResourceService $webResourceService,
        StateService $stateService
    ) {
        $this->setHttpClientService($httpClientService);
        $this->setWebResourceService($webResourceService);
        $this->setStateService($stateService);
    }

    /**
     * @inheritdoc
     */
    protected function hasNotSucceededHandler()
    {
        $this->response->setErrorCount(1);

        return json_encode($this->getWebResourceExceptionOutput());
    }

    /**
     * @inheritdoc
     */
    protected function isNotCorrectWebResourceTypeHandler()
    {
        $this->response->setHasBeenSkipped();
        $this->response->setIsRetryable(false);
        $this->response->setErrorCount(0);
    }

    /**
     * @inheritdoc
     */
    protected function isBlankWebResourceHandler()
    {
        $this->response->setHasBeenSkipped();
        $this->response->setErrorCount(0);
    }

    /**
     * @inheritdoc
     */
    protected function performValidation()
    {
        $finder = new HtmlDocumentLinkUrlFinder();
        $finder->getConfiguration()->setSource($this->webResource);
        $finder->getConfiguration()->setSourceUrl($this->webResource->getUrl());
        $finder->getConfiguration()->setElementScope('a');
        $finder->getConfiguration()->enableIgnoreFragmentInUrlComparison();
        $finder->getUrlScopeComparer()->addEquivalentSchemes($this->equivalentSchemes);

        if ($this->task->hasParameter('scope')) {
            $finder->getConfiguration()->setUrlScope($this->task->getParameter('scope'));
        }

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
