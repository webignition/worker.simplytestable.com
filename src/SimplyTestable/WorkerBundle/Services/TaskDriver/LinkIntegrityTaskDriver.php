<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use webignition\InternetMediaType\InternetMediaType;
use webignition\UrlHealthChecker\Configuration as UrlHealthCheckerConfiguration;
use webignition\WebResource\Service\Service as WebResourceService;
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
        $urlHealthCheckerConfiguration = new UrlHealthCheckerConfiguration([
            UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_METHOD_LIST => [
                LinkCheckerConfiguration::HTTP_METHOD_GET
            ],
            UrlHealthCheckerConfiguration::CONFIG_KEY_TOGGLE_URL_ENCODING => true,
            UrlHealthCheckerConfiguration::CONFIG_KEY_RETRY_ON_BAD_RESPONSE => false,
            UrlHealthCheckerConfiguration::CONFIG_KEY_USER_AGENTS => $this->userAgents,
            UrlHealthCheckerConfiguration::CONFIG_KEY_HTTP_CLIENT => $this->getHttpClientService()->get()
        ]);

        $linkChecker = new LinkChecker();
        $linkChecker->getUrlHealthChecker()->setConfiguration($urlHealthCheckerConfiguration);
        $linkChecker->setWebPage($this->webResource);

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

        $linkChecker->getConfiguration()->enableIgnoreFragmentInUrlComparison();

        return $linkChecker;
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
