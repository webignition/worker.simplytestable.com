<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Exception\UnableToPerformTaskException;
use App\Model\LinkIntegrityResult;
use App\Model\LinkIntegrityResultCollection;
use App\Model\Task\Type;
use App\Model\Task\Parameters as TaskParameters;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpRetryMiddleware;
use App\Services\TaskCachedSourceWebPageRetriever;
use webignition\IgnoredUrlVerifier\IgnoredUrlVerifier;
use webignition\InternetMediaType\InternetMediaType;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkFinderConfiguration;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;

class LinkIntegrityTaskTypePerformer
{
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';
    const EXCLUDED_URLS_PARAMETER_NAME = 'excluded-urls';
    const EXCLUDED_DOMAINS_PARAMETER_NAME = 'excluded-domains';

    private $httpClientConfigurationService;
    private $taskCachedSourceWebPageRetriever;
    private $httpRetryMiddleware;
    private $linkChecker;

    public function __construct(
        HttpClientConfigurationService $httpClientConfigurationService,
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        HttpRetryMiddleware $httpRetryMiddleware,
        LinkChecker $linkChecker
    ) {
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;

        $this->httpRetryMiddleware = $httpRetryMiddleware;
        $this->linkChecker = $linkChecker;
    }

    /**
     * @param TaskEvent $taskEvent
     *
     * @throws UnableToPerformTaskException
     */
    public function __invoke(TaskEvent $taskEvent)
    {
        if (Type::TYPE_LINK_INTEGRITY === (string) $taskEvent->getTask()->getType()) {
            $this->perform($taskEvent->getTask());
        }
    }

    /**
     * @param Task $task
     *
     * @return null
     *
     * @throws UnableToPerformTaskException
     */
    public function perform(Task $task)
    {
        if (!empty($task->getOutput())) {
            return null;
        }

        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);
        if (empty($webPage)) {
            throw new UnableToPerformTaskException();
        }

        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        return $this->performValidation($task, $webPage);
    }

    private function performValidation(Task $task, WebPage $webPage)
    {
        $exclusions = $this->createIgnoredUrlVerifierExclusions($task->getParameters());

        $linkIntegrityResultCollection = new LinkIntegrityResultCollection();

        $this->httpRetryMiddleware->disable();

        $links = $this->findWebPageLinks($webPage);
        foreach ($links as $link) {
            $url = rawurldecode($link['url']);

            if (!(new IgnoredUrlVerifier())->isUrlIgnored($url, $exclusions)) {
                $linkState = $this->linkChecker->getLinkState($url);

                if ($linkState) {
                    $linkIntegrityResultCollection->add(new LinkIntegrityResult(
                        $url,
                        $link['element'],
                        $linkState
                    ));
                }
            }
        }

        $this->httpRetryMiddleware->enable();

        $task->setOutput(Output::create(
            (string) json_encode($linkIntegrityResultCollection),
            new InternetMediaType('application', 'json'),
            $linkIntegrityResultCollection->getErrorCount()
        ));

        $task->setState(Task::STATE_COMPLETED);

        return null;
    }

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

    private function createIgnoredUrlVerifierExclusions(TaskParameters $parameters)
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
