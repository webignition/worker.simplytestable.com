<?php

namespace SimplyTestable\AppBundle\Services\TaskDriver;

use GuzzleHttp\Exception\GuzzleException;
use QueryPath\Exception as QueryPathException;
use SimplyTestable\AppBundle\Services\HttpClientConfigurationService;
use SimplyTestable\AppBundle\Services\HttpClientService;
use SimplyTestable\AppBundle\Services\HttpRetryMiddleware;
use SimplyTestable\AppBundle\Services\StateService;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\WebResource\WebPage\WebPage;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

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

    /**
     * @param StateService $stateService
     * @param HttpClientService $httpClientService
     * @param HttpClientConfigurationService $httpClientConfigurationService
     * @param WebResourceRetriever $webResourceRetriever
     * @param HttpHistoryContainer $httpHistoryContainer
     * @param LinkCheckerConfigurationFactory $linkCheckerConfigurationFactory
     * @param HttpRetryMiddleware $httpRetryMiddleware
     */
    public function __construct(
        StateService $stateService,
        HttpClientService $httpClientService,
        HttpClientConfigurationService $httpClientConfigurationService,
        WebResourceRetriever $webResourceRetriever,
        HttpHistoryContainer $httpHistoryContainer,
        LinkCheckerConfigurationFactory $linkCheckerConfigurationFactory,
        HttpRetryMiddleware $httpRetryMiddleware
    ) {
        parent::__construct(
            $stateService,
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
     *
     * @throws GuzzleException
     * @throws QueryPathException
     */
    protected function performValidation(WebPage $webPage)
    {
        $linkChecker = new LinkChecker(
            $this->linkCheckerConfigurationFactory->create($this->task),
            $this->httpClientService->getHttpClient()
        );

        $linkChecker->setWebPage($webPage);

        $this->httpRetryMiddleware->disable();

        $linkCheckResults = $linkChecker->getAll();

        $this->httpRetryMiddleware->enable();

        $this->response->setErrorCount(count($linkChecker->getErrored()));

        return json_encode($this->getOutputObject($linkCheckResults));
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
     * @param array $linkCheckResults
     *
     * @return array
     */
    private function getOutputObject($linkCheckResults)
    {
        $outputObject = [];

        foreach ($linkCheckResults as $linkCheckResult) {
            $outputObject[] = [
                'context' => $linkCheckResult->getContext(),
                'state' => $linkCheckResult->getLinkState()->getState(),
                'type' => $linkCheckResult->getLinkState()->getType(),
                'url' => $linkCheckResult->getUrl()
            ];
        }

        return $outputObject;
    }
}
