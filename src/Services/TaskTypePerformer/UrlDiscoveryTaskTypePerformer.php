<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Exception\UnableToPerformTaskException;
use App\Model\Task\Type;
use App\Services\TaskCachedSourceWebPageRetriever;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkUrlFinderConfiguration;
use webignition\InternetMediaType\InternetMediaType;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;

class UrlDiscoveryTaskTypePerformer
{
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';

    private $taskCachedSourceWebPageRetriever;

    /**
     * @var string[]
     */
    private $equivalentSchemes = [
        'http',
        'https'
    ];

    public function __construct(TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever)
    {
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;
    }

    /**
     * @param TaskEvent $taskEvent
     *
     * @throws UnableToPerformTaskException
     */
    public function __invoke(TaskEvent $taskEvent)
    {
        if (Type::TYPE_URL_DISCOVERY === (string) $taskEvent->getTask()->getType()) {
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
            (string) json_encode($finder->getUniqueUrls()),
            new InternetMediaType('application', 'json')
        ));

        $task->setState(Task::STATE_COMPLETED);

        return null;
    }
}
