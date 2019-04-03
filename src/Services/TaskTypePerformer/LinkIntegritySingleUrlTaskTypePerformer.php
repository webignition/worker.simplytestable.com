<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\LinkIntegrityResult;
use App\Model\Task\Type;
use App\Model\Task\Parameters as TaskParameters;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpRetryMiddleware;
use webignition\IgnoredUrlVerifier\IgnoredUrlVerifier;
use webignition\InternetMediaType\InternetMediaType;
use webignition\HtmlDocument\LinkChecker\LinkChecker;

class LinkIntegritySingleUrlTaskTypePerformer
{
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';
    const EXCLUDED_URLS_PARAMETER_NAME = 'excluded-urls';
    const EXCLUDED_DOMAINS_PARAMETER_NAME = 'excluded-domains';

    private $httpClientConfigurationService;
    private $httpRetryMiddleware;
    private $linkChecker;

    public function __construct(
        HttpClientConfigurationService $httpClientConfigurationService,
        HttpRetryMiddleware $httpRetryMiddleware,
        LinkChecker $linkChecker
    ) {
        $this->httpClientConfigurationService = $httpClientConfigurationService;

        $this->httpRetryMiddleware = $httpRetryMiddleware;
        $this->linkChecker = $linkChecker;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        if (Type::TYPE_LINK_INTEGRITY_SINGLE_URL === (string) $taskEvent->getTask()->getType()) {
            $this->perform($taskEvent->getTask());
        }
    }

    public function perform(Task $task)
    {
        if (!empty($task->getOutput())) {
            return null;
        }

        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        return $this->performValidation($task);
    }

    private function performValidation(Task $task)
    {
        $this->httpRetryMiddleware->disable();

        $url = $task->getUrl();
        $element = $task->getParameters()->get('element');

        $outputContent = '';
        $outputContentType = new InternetMediaType('application', 'json');
        $outputErrorCount = 0;

        $exclusions = $this->createUrlExclusions($task->getParameters());

        if (!(new IgnoredUrlVerifier())->isUrlIgnored($url, $exclusions)) {
            $linkState = $this->linkChecker->getLinkState($url);

            if ($linkState) {
                $linkInterityResult = new LinkIntegrityResult(
                    $url,
                    $element,
                    $linkState
                );

                $outputContent = $linkInterityResult;
                $outputErrorCount = (int) $linkState->isError();
            }
        }

        $task->setOutput(Output::create(
            (string) json_encode($outputContent),
            $outputContentType,
            $outputErrorCount
        ));

        $this->httpRetryMiddleware->enable();

        $task->setState(Task::STATE_COMPLETED);

        return null;
    }

    private function createUrlExclusions(TaskParameters $parameters)
    {
        $excludedHosts = $parameters->get(self::EXCLUDED_DOMAINS_PARAMETER_NAME);
        $excludedHosts = $excludedHosts ?? [];

        $excludedUrls = $parameters->get(self::EXCLUDED_URLS_PARAMETER_NAME);
        $excludedUrls = $excludedUrls ?? [];

        return [
            IgnoredUrlVerifier::EXCLUSIONS_SCHEMES => [
                IgnoredUrlVerifier::URL_SCHEME_MAILTO,
                IgnoredUrlVerifier::URL_SCHEME_ABOUT,
                IgnoredUrlVerifier::URL_SCHEME_JAVASCRIPT,
                IgnoredUrlVerifier::URL_SCHEME_FTP,
                IgnoredUrlVerifier::URL_SCHEME_TEL,
            ],
            IgnoredUrlVerifier::EXCLUSIONS_HOSTS => $excludedHosts,
            IgnoredUrlVerifier::EXCLUSIONS_URLS => $excludedUrls,
        ];
    }
}
