<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\LinkIntegrityResult;
use App\Model\LinkIntegrityResultCollection;
use App\Services\HttpClientConfigurationService;
use App\Services\HttpClientService;
use App\Services\HttpRetryMiddleware;
use App\Services\TaskPerformerTaskOutputMutator;
use App\Services\TaskPerformerWebPageRetriever;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Exception\TransportException;
use webignition\HtmlDocument\LinkChecker\LinkChecker;
use webignition\WebResource\WebPage\WebPage;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkFinderConfiguration;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;

class LinkIntegrityTaskTypePerformer implements TaskTypePerformerInterface
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
     * @var TaskPerformerWebPageRetriever
     */
    private $taskPerformerWebPageRetriever;

    /**
     * @var TaskPerformerTaskOutputMutator
     */
    private $taskPerformerTaskOutputMutator;

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
        TaskPerformerWebPageRetriever $taskPerformerWebPageRetriever,
        TaskPerformerTaskOutputMutator $taskPerformerTaskOutputMutator,
        LinkCheckerConfigurationFactory $linkCheckerConfigurationFactory,
        HttpRetryMiddleware $httpRetryMiddleware
    ) {
        $this->httpClientService = $httpClientService;
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->taskPerformerWebPageRetriever = $taskPerformerWebPageRetriever;
        $this->taskPerformerTaskOutputMutator = $taskPerformerTaskOutputMutator;

        $this->linkCheckerConfigurationFactory = $linkCheckerConfigurationFactory;
        $this->httpRetryMiddleware = $httpRetryMiddleware;
    }

    /**
     * @param Task $task
     *
     * @return null
     *
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     */
    public function perform(Task $task)
    {
        $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

        $result = $this->taskPerformerWebPageRetriever->retrieveWebPage($task);
        $task->setState($result->getTaskState());

        if (!$task->isIncomplete()) {
            $this->taskPerformerTaskOutputMutator->mutate($task, $result->getTaskOutputValues());

            return null;
        }

        return $this->performValidation($task, $result->getWebPage());
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
            new InternetMediaType('application/json'),
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
