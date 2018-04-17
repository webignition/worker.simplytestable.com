<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use GuzzleHttp\Exception\GuzzleException;
use QueryPath\Exception as QueryPathException;
use SimplyTestable\WorkerBundle\Services\FooHttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\WebResource\WebPage\WebPage;

class LinkIntegrityTaskDriver extends WebResourceTaskDriver
{
    const COOKIES_PARAMETER_NAME = 'cookies';

    /**
     * @var LinkCheckerConfigurationFactory
     */
    private $linkCheckerConfigurationFactory;

    /**
     * @param StateService $stateService
     * @param FooHttpClientService $fooHttpClientService
     * @param WebResourceRetriever $webResourceRetriever
     * @param LinkCheckerConfigurationFactory $linkCheckerConfigurationFactory
     */
    public function __construct(
        StateService $stateService,
        FooHttpClientService $fooHttpClientService,
        WebResourceRetriever $webResourceRetriever,
        LinkCheckerConfigurationFactory $linkCheckerConfigurationFactory
    ) {
        parent::__construct($stateService, $fooHttpClientService, $webResourceRetriever);

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
    protected function performValidation()
    {
        $linkChecker = new LinkChecker(
            $this->linkCheckerConfigurationFactory->create($this->task),
            $this->fooHttpClientService->getHttpClient()
        );

        /* @var WebPage $webPage */
        $webPage = $this->webResource;
        $linkChecker->setWebPage($webPage);

        $this->fooHttpClientService->disableRetryMiddleware();

        $linkCheckResults = $linkChecker->getAll();

        $this->fooHttpClientService->enableRetryMiddleware();

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
