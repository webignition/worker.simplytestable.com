<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Services\HttpClientConfigurationService;
use App\Services\TaskPerformerWebPageRetriever;
use webignition\HtmlDocumentLinkUrlFinder\Configuration as LinkUrlFinderConfiguration;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\InternetMediaType\InternetMediaType;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\WebPage\WebPage;

class UrlDiscoveryTaskTypePerformer implements TaskTypePerformerInterface
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
     * @var string[]
     */
    private $equivalentSchemes = [
        'http',
        'https'
    ];

    public function __construct(
        TaskPerformerWebPageRetriever $taskPerformerWebPageRetriever,
        HttpClientConfigurationService $httpClientConfigurationService
    ) {
        $this->taskPerformerWebPageRetriever = $taskPerformerWebPageRetriever;
        $this->httpClientConfigurationService = $httpClientConfigurationService;
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

        $webPage = $this->taskPerformerWebPageRetriever->retrieveWebPage($task);

        if (!$task->isIncomplete()) {
            return null;
        }

        return $this->performValidation($task, $webPage);
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
            new InternetMediaType('application/json'),
            0
        ));

        $task->setState(Task::STATE_COMPLETED);
    }
}
