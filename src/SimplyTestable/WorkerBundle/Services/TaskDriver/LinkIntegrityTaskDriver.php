<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Service\Service as WebResourceService;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\HtmlDocument\LinkChecker\Configuration as LinkCheckerConfiguration;

class LinkIntegrityTaskDriver extends WebResourceTaskDriver
{
    const EXCLUDED_URLS_PARAMETER_NAME = 'excluded-urls';
    const EXCLUDED_DOMAINS_PARAMETER_NAME = 'excluded-domains';
    const COOKIES_PARAMETER_NAME = 'cookies';

    /**
     * @var string[]
     */
    private $userAgents;

    /**
     * @param HttpClientService $httpClientService
     * @param WebResourceService $webResourceService
     * @param StateService $stateService
     * @param string[] $userAgents
     */
    public function __construct(
        HttpClientService $httpClientService,
        WebResourceService $webResourceService,
        StateService $stateService,
        $userAgents
    ) {
        $this->setHttpClientService($httpClientService);
        $this->setWebResourceService($webResourceService);
        $this->setStateService($stateService);
        $this->setUserAgents($userAgents);
    }

    /**
     * @param string[] $userAgents
     */
    private function setUserAgents(array $userAgents)
    {
        $this->userAgents = $userAgents;
    }

    /**
     * @return string
     */
    protected function hasNotSucceedHandler()
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
        $linkChecker = $this->createLinkChecker();

        $this->getHttpClientService()->disableRetrySubscriber();
        $this->getHttpClientService()->setCookies($this->task->getParameter('cookies'));
        $this->getHttpClientService()->setBasicHttpAuthorization(
            $this->task->getParameter('http-auth-username'),
            $this->task->getParameter('http-auth-password')
        );

        $linkCheckResults = $linkChecker->getAll();

        $this->getHttpClientService()->clearCookies();
        $this->getHttpClientService()->clearBasicHttpAuthorization();
        $this->getHttpClientService()->enableRetrySubscriber();

        $this->response->setErrorCount(count($linkChecker->getErrored()));
        return json_encode($this->getOutputObject($linkCheckResults));
    }

    /**
     * @return LinkChecker
     */
    private function createLinkChecker()
    {
        $linkChecker = new LinkChecker();
        $linkChecker->setWebPage($this->webResource);
        $linkChecker->getUrlHealthChecker()->getConfiguration()->setHttpMethodList(array(
            LinkCheckerConfiguration::HTTP_METHOD_GET
        ));

        if ($this->task->hasParameter(self::EXCLUDED_URLS_PARAMETER_NAME)) {
            $linkChecker->getConfiguration()->setUrlsToExclude(
                $this->task->getParameter(self::EXCLUDED_URLS_PARAMETER_NAME)
            );
        }

        if ($this->task->hasParameter(self::EXCLUDED_DOMAINS_PARAMETER_NAME)) {
            $linkChecker->getConfiguration()->setDomainsToExclude(
                $this->task->getParameter(self::EXCLUDED_DOMAINS_PARAMETER_NAME)
            );
        }

        $linkChecker->getUrlHealthChecker()->getConfiguration()->enableToggleUrlEncoding();
        $linkChecker->getUrlHealthChecker()->getConfiguration()->disableRetryOnBadResponse();
        $linkChecker->getConfiguration()->enableIgnoreFragmentInUrlComparison();

        $linkChecker->getUrlHealthChecker()->getConfiguration()->setUserAgents($this->userAgents);
        $linkChecker->getUrlHealthChecker()->getConfiguration()->setHttpClient($this->getHttpClientService()->get());

        return $linkChecker;
    }

    /**
     * @return boolean
     */
    protected function isCorrectWebResourceType()
    {
        return $this->webResource instanceof WebPage;
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
        $outputObject = array();

        foreach ($linkCheckResults as $linkCheckResult) {
            $outputObject[] = array(
                'context' => $linkCheckResult->getContext(),
                'state' => $linkCheckResult->getLinkState()->getState(),
                'type' => $linkCheckResult->getLinkState()->getType(),
                'url' => $linkCheckResult->getUrl()
            );
        }

        return $outputObject;
    }
}
