<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use GuzzleHttp\Exception\GuzzleException;
use QueryPath\Exception as QueryPathException;
use SimplyTestable\WorkerBundle\Services\HttpClientConfigurationService;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\WebResource\WebPage\WebPage;

class LinkIntegrityTaskDriver extends AbstractWebPageTaskDriver
{
    const COOKIES_PARAMETER_NAME = 'cookies';

    /**
     * @var LinkCheckerConfigurationFactory
     */
    private $linkCheckerConfigurationFactory;

    /**
     * @param StateService $stateService
     * @param HttpClientService $httpClientService
     * @param HttpClientConfigurationService $httpClientConfigurationService
     * @param WebResourceRetriever $webResourceRetriever
     * @param LinkCheckerConfigurationFactory $linkCheckerConfigurationFactory
     */
    public function __construct(
        StateService $stateService,
        HttpClientService $httpClientService,
        HttpClientConfigurationService $httpClientConfigurationService,
        WebResourceRetriever $webResourceRetriever,
        LinkCheckerConfigurationFactory $linkCheckerConfigurationFactory
    ) {
        parent::__construct($stateService, $httpClientService, $httpClientConfigurationService, $webResourceRetriever);

        $this->linkCheckerConfigurationFactory = $linkCheckerConfigurationFactory;
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

        $this->httpClientService->disableRetryMiddleware();

        $linkCheckResults = $linkChecker->getAll();

        $this->httpClientService->enableRetryMiddleware();

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
