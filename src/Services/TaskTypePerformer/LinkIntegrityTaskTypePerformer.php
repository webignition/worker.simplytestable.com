<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\LinkIntegrityResult;
use App\Model\LinkIntegrityResultCollection;
use App\Model\Task\TypeInterface;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpClientService;
use App\Services\HttpRetryMiddleware;
use App\Services\TaskCachedSourceWebPageRetriever;
use webignition\InternetMediaType\InternetMediaType;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkFinderConfiguration;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;

class LinkIntegrityTaskTypePerformer implements TaskPerformerInterface
{
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var HttpClientConfigurationService
     */
    private $httpClientConfigurationService;

    /**
     * @var TaskCachedSourceWebPageRetriever
     */
    private $taskCachedSourceWebPageRetriever;

    /**
     * @var LinkCheckerConfigurationFactory
     */
    private $linkCheckerConfigurationFactory;

    /**
     * @var HttpRetryMiddleware
     */
    private $httpRetryMiddleware;

    /**
     * @var int
     */
    private $priority;

    public function __construct(
        HttpClientService $httpClientService,
        HttpClientConfigurationService $httpClientConfigurationService,
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        LinkCheckerConfigurationFactory $linkCheckerConfigurationFactory,
        HttpRetryMiddleware $httpRetryMiddleware,
        int $priority
    ) {
        $this->httpClientService = $httpClientService;
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;

        $this->linkCheckerConfigurationFactory = $linkCheckerConfigurationFactory;
        $this->httpRetryMiddleware = $httpRetryMiddleware;
        $this->priority = $priority;
    }

    public function perform(Task $task)
    {
        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        return $this->performValidation($task, $this->taskCachedSourceWebPageRetriever->retrieve($task));
    }

    public function handles(string $taskType): bool
    {
        return TypeInterface::TYPE_LINK_INTEGRITY === $taskType;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    private function performValidation(Task $task, WebPage $webPage)
    {
        $linkChecker = new LinkChecker(
            $this->linkCheckerConfigurationFactory->create($task),
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

        $task->setOutput(Output::create(
            json_encode($linkIntegrityResultCollection),
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
}
