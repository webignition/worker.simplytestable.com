<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use webignition\WebResource\Service\Service as WebResourceService;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;

class UrlDiscoveryTaskDriver extends WebResourceTaskDriver {

    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';

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



}