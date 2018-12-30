<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\TaskCachedSourceWebPageRetriever;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkUrlFinderConfiguration;
use webignition\InternetMediaType\InternetMediaType;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;

class UrlDiscoveryTaskTypePerformer implements TaskPerformerInterface
{
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';

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

    public function __construct(TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever, int $priority)
    {
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;
        $this->priority = $priority;
    }

    public function perform(Task $task)
    {
        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);

        $configuration = new LinkUrlFinderConfiguration([
            LinkUrlFinderConfiguration::CONFIG_KEY_SOURCE => $webPage,
            LinkUrlFinderConfiguration::CONFIG_KEY_SOURCE_URL => (string) $webPage->getUri(),
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

    public function handles(string $taskType): bool
    {
        return TypeInterface::TYPE_URL_DISCOVERY === $taskType;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
