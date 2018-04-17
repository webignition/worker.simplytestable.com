<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use QueryPath\Exception as QueryPathException;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkUrlFinderConfiguration;
use webignition\InternetMediaType\InternetMediaType;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;

class UrlDiscoveryTaskDriver extends WebResourceTaskDriver
{
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';

    /**
     * @var string[]
     */
    private $equivalentSchemes = [
        'http',
        'https'
    ];

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
     * @throws QueryPathException
     */
    protected function performValidation()
    {
        $configuration = new LinkUrlFinderConfiguration([
            LinkUrlFinderConfiguration::CONFIG_KEY_SOURCE => $this->webResource,
            LinkUrlFinderConfiguration::CONFIG_KEY_SOURCE_URL => (string)$this->webResource->getUri(),
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
