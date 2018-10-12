<?php

namespace App\Services\TaskDriver;

use App\Model\LinkIntegrityResult;
use App\Model\LinkIntegrityResultCollection;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpClientService;
use App\Services\HttpRetryMiddleware;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\WebResource\WebPage\WebPage;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkFinderConfiguration;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;

class LinkIntegrityTaskDriver extends AbstractWebPageTaskDriver
{
    const COOKIES_PARAMETER_NAME = 'cookies';

    /**
     * @var LinkCheckerConfigurationFactory
     */
    private $linkCheckerConfigurationFactory;

    /**
     * @var HttpRetryMiddleware
     */
    private $httpRetryMiddleware;

    public function __construct(
        HttpClientService $httpClientService,
        HttpClientConfigurationService $httpClientConfigurationService,
        WebResourceRetriever $webResourceRetriever,
        HttpHistoryContainer $httpHistoryContainer,
        LinkCheckerConfigurationFactory $linkCheckerConfigurationFactory,
        HttpRetryMiddleware $httpRetryMiddleware
    ) {
        parent::__construct(
            $httpClientService,
            $httpClientConfigurationService,
            $webResourceRetriever,
            $httpHistoryContainer
        );

        $this->linkCheckerConfigurationFactory = $linkCheckerConfigurationFactory;
        $this->httpRetryMiddleware = $httpRetryMiddleware;
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
     */
    protected function performValidation(WebPage $webPage)
    {
        $linkChecker = new LinkChecker(
            $this->linkCheckerConfigurationFactory->create($this->task),
            $this->httpClientService->getHttpClient()
        );

        $linkIntegrityResultCollection = new LinkIntegrityResultCollection();

        $this->httpRetryMiddleware->disable();

        $links = $this->findWebPageLinks($webPage);
        foreach ($links as $link) {
            $link['url'] = rawurldecode($link['url']);

            $linkState = $linkChecker->getLinkState($link['url']);

            if ($linkState) {
                $linkIntegrityResultCollection->add(new LinkIntegrityResult(
                    $link['url'],
                    $link['element'],
                    $linkState
                ));
            }
        }

        $this->httpRetryMiddleware->enable();

        $this->response->setErrorCount($linkIntegrityResultCollection->getErrorCount());

        return json_encode($linkIntegrityResultCollection);
    }

    /**
     * @param WebPage $webPage
     *
     * @return array
     */
    private function findWebPageLinks(WebPage $webPage): array
    {
        $linkFinderConfiguration = new LinkFinderConfiguration([
            LinkFinderConfiguration::CONFIG_KEY_SOURCE => $webPage,
            LinkFinderConfiguration::CONFIG_KEY_SOURCE_URL => (string)$webPage->getUri(),
        ]);

        $linkFinder = new HtmlDocumentLinkUrlFinder();
        $linkFinder->setConfiguration($linkFinderConfiguration);

        return $linkFinder->getAll();
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
