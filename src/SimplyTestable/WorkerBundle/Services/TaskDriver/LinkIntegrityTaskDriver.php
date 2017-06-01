<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use webignition\WebResource\Service\Service as WebResourceService;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\HtmlDocument\LinkChecker\Configuration as LinkCheckerConfiguration;

class LinkIntegrityTaskDriver extends WebResourceTaskDriver {

    const EXCLUDED_URLS_PARAMETER_NAME = 'excluded-urls';
    const EXCLUDED_DOMAINS_PARAMETER_NAME = 'excluded-domains';
    const COOKIES_PARAMETER_NAME = 'cookies';

    /**
     * @var string[]
     */
    private $userAgents;

    /**
     * @param TaskTypeService $taskTypeService
     * @param HttpClientService $httpClientService
     * @param WebResourceService $webResourceService
     * @param StateService $stateService
     * @param string[] $userAgents
     */
    public function __construct(
        TaskTypeService $taskTypeService,
        HttpClientService $httpClientService,
        WebResourceService $webResourceService,
        StateService $stateService,
        $userAgents
    ) {
        $this->setTaskTypeService($taskTypeService);
        $this->setHttpClientService($httpClientService);
        $this->setWebResourceService($webResourceService);
        $this->setStateService($stateService);
        $this->setUserAgents($userAgents);
    }

    private function setUserAgents(array $userAgents)
    {
        $this->userAgents = $userAgents;
    }

    /**
     *
     * @param \SimplyTestable\WorkerBundle\Entity\Task\Task $task
     * @return boolean
     */
    protected function isCorrectTaskType(Task $task) {
        return $task->getType()->equals($this->getTaskTypeService()->getLinkIntegrityTaskType());
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
        $linkChecker = $this->getLinkChecker();

        $linkCheckResults = $linkChecker->getAll();

        $this->getHttpClientService()->enablePlugins();

        $this->response->setErrorCount(count($linkChecker->getErrored()));
        return json_encode($this->getOutputOject($linkCheckResults));
    }


    /**
     *
     * @return \webignition\HtmlDocument\LinkChecker\LinkChecker
     */
    private function getLinkChecker() {
        $linkChecker = new LinkChecker();
        $linkChecker->setWebPage($this->webResource);
        $linkChecker->getUrlHealthChecker()->getConfiguration()->setHttpMethodList(array(
            LinkCheckerConfiguration::HTTP_METHOD_GET
        ));

        if ($this->task->hasParameter(self::EXCLUDED_URLS_PARAMETER_NAME)) {
            $linkChecker->getConfiguration()->setUrlsToExclude($this->task->getParameter(self::EXCLUDED_URLS_PARAMETER_NAME));
        }

        if ($this->task->hasParameter(self::EXCLUDED_DOMAINS_PARAMETER_NAME)) {
            $linkChecker->getConfiguration()->setDomainsToExclude($this->task->getParameter(self::EXCLUDED_DOMAINS_PARAMETER_NAME));
        }

        $linkChecker->getUrlHealthChecker()->getConfiguration()->enableToggleUrlEncoding();
        $linkChecker->getUrlHealthChecker()->getConfiguration()->disableRetryOnBadResponse();
        $linkChecker->getConfiguration()->enableIgnoreFragmentInUrlComparison();

        $this->getHttpClientService()->disablePlugin('Guzzle\Plugin\Backoff\BackoffPlugin');

        $linkChecker->getUrlHealthChecker()->getConfiguration()->setUserAgents($this->userAgents);

        $baseRequest = clone $this->getBaseRequest();
        $baseRequest->getCurlOptions()->set(CURLOPT_TIMEOUT_MS, 10000);
        $linkChecker->getUrlHealthChecker()->getConfiguration()->setBaseRequest($baseRequest);

        return $linkChecker;
    }


    protected function getMissingDocumentTypeOutput() {
        $outputObjectMessage = new \stdClass();
        $outputObjectMessage->message = 'No doctype';
        $outputObjectMessage->messageId = 'document-type-missing';
        $outputObjectMessage->type = 'error';

        $outputObject = new \stdClass();
        $outputObject->messages = array($outputObjectMessage);

        return $outputObject;
    }

    protected function getIsNotMarkupOutput($fragment) {
        $outputObjectMessage = new \stdClass();
        $outputObjectMessage->message = 'Not markup';
        $outputObjectMessage->messageId = 'document-is-not-markup';
        $outputObjectMessage->type = 'error';
        $outputObjectMessage->fragment = $fragment;

        $outputObject = new \stdClass();
        $outputObject->messages = array($outputObjectMessage);

        return $outputObject;
    }

    protected function getInvalidDocumentTypeOutput($documentType) {
        $outputObjectMessage = new \stdClass();
        $outputObjectMessage->message = $documentType;
        $outputObjectMessage->messageId = 'document-type-invalid';
        $outputObjectMessage->type = 'error';

        $outputObject = new \stdClass();
        $outputObject->messages = array($outputObjectMessage);

        return $outputObject;
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



    /**
     *
     * @param array $linkCheckResults
     * @return \stdClass
     */
    private function getOutputOject($linkCheckResults) {
        $outputObject = array();


        /* @var $linkState \webignition\HtmlDocumentLinkChecker\LinkCheckResult */
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