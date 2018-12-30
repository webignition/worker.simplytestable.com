<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\HttpClientConfigurationService;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Services\TaskPerformerTaskOutputMutator;
use App\Services\TaskPerformerWebPageRetriever;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkUrlFinderConfiguration;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\InternetMediaType\InternetMediaType;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\WebPage\WebPage;

class UrlDiscoveryTaskTypePerformer implements TaskPerformerInterface
{
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';

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
     * @var TaskCachedSourceWebPageRetriever
     */
    private $taskCachedSourceWebPageRetriever;

    /**
     * @var string[]
     */
    private $equivalentSchemes = [
        'http',
        'https'
    ];

    /**
     * @var int
     */
    private $priority;

    public function __construct(
        TaskPerformerWebPageRetriever $taskPerformerWebPageRetriever,
        TaskPerformerTaskOutputMutator $taskPerformerTaskOutputMutator,
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        HttpClientConfigurationService $httpClientConfigurationService,
        int $priority
    ) {
        $this->taskPerformerWebPageRetriever = $taskPerformerWebPageRetriever;
        $this->taskPerformerTaskOutputMutator = $taskPerformerTaskOutputMutator;
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;
        $this->httpClientConfigurationService = $httpClientConfigurationService;
        $this->priority = $priority;
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
        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);

        if (empty($webPage)) {
            $this->httpClientConfigurationService->configureForTask($task, self::USER_AGENT);

            $result = $this->taskPerformerWebPageRetriever->retrieveWebPage($task);
            $task->setState($result->getTaskState());

            if (!$task->isIncomplete()) {
                $this->taskPerformerTaskOutputMutator->mutate($task, $result->getTaskOutputValues());

                return null;
            }

            $webPage = $result->getWebPage();
        }

        return $this->performValidation($task, $webPage);
    }

    public function handles(string $taskType): bool
    {
        return TypeInterface::TYPE_URL_DISCOVERY === $taskType;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }


    private function performValidation(Task $task, WebPage $webPage)
    {
        $configuration = new LinkUrlFinderConfiguration([
            LinkUrlFinderConfiguration::CONFIG_KEY_SOURCE => $webPage,
            LinkUrlFinderConfiguration::CONFIG_KEY_SOURCE_URL => (string)$webPage->getUri(),
            LinkUrlFinderConfiguration::CONFIG_KEY_ELEMENT_SCOPE => 'a',
            LinkUrlFinderConfiguration::CONFIG_KEY_IGNORE_FRAGMENT_IN_URL_COMPARISON => true,
        ]);

        $urlScope = $task->getParameters()->get('scope');
        if ($urlScope) {
            $configuration->setUrlScope($urlScope);
        }

        $finder = new HtmlDocumentLinkUrlFinder();
        $finder->setConfiguration($configuration);
        $finder->getUrlScopeComparer()->addEquivalentSchemes($this->equivalentSchemes);

        $task->setOutput(Output::create(
            json_encode($finder->getUniqueUrls()),
            new InternetMediaType('application', 'json'),
            0
        ));

        $task->setState(Task::STATE_COMPLETED);
    }
}
