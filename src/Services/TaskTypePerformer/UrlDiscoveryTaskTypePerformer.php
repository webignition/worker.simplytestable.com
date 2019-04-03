<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Exception\UnableToPerformTaskException;
use App\Model\Task\Parameters;
use App\Model\Task\Type;
use App\Services\TaskCachedSourceWebPageRetriever;
use Psr\Http\Message\UriInterface;
use webignition\InternetMediaType\InternetMediaType;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use webignition\Uri\ScopeComparer;
use webignition\Uri\Uri;

class UrlDiscoveryTaskTypePerformer
{
    const USER_AGENT = 'ST Web Resource Task Driver (http://bit.ly/RlhKCL)';
    const DEFAULT_CHARACTER_ENCODING = 'UTF-8';

    private $taskCachedSourceWebPageRetriever;
    private $linkFinder;

    /**
     * @var string[]
     */
    private $equivalentSchemes = [
        'http',
        'https'
    ];

    public function __construct(
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        HtmlDocumentLinkUrlFinder $linkFinder
    ) {
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;
        $this->linkFinder = $linkFinder;
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

        $scopeComparer = new ScopeComparer();
        $scopeComparer->addEquivalentSchemes($this->equivalentSchemes);

        $linkCollection = $this->linkFinder->getLinkCollection($webPage, (string) $webPage->getUri());
        $linkCollection = $linkCollection->filterByElementName('a');

        $uriScope = $this->createUriScope($task->getParameters());
        if (!empty($uriScope)) {
            $linkCollection = $linkCollection->filterByUriScope($scopeComparer, $uriScope);
        }

        $unqiueUris = $linkCollection->getUniqueUris();
        $unqiueUriStrings = [];

        foreach ($unqiueUris as $uri) {
            $unqiueUriStrings[] = (string) $uri;
        }

        $task->setOutput(Output::create(
            (string) json_encode($unqiueUriStrings),
            new InternetMediaType('application', 'json')
        ));

        $task->setState(Task::STATE_COMPLETED);

        return null;
    }

    /**
     * @param Parameters $parameters
     *
     * @return UriInterface[]
     */
    private function createUriScope(Parameters $parameters): array
    {
        $uriScope = [];
        $scopeParameter = $parameters->get('scope');

        if ($scopeParameter && is_array($scopeParameter)) {
            foreach ($scopeParameter as $uriString) {
                $uriScope[] = new Uri($uriString);
            }
        }

        return $uriScope;
    }
}
