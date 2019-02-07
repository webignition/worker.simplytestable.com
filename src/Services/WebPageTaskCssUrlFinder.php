<?php

namespace App\Services;

use App\Entity\Task\Task;
use App\Model\Source;

class WebPageTaskCssUrlFinder
{
    private $taskCachedSourceWebPageRetriever;
    private $cssSourceInspector;
    private $cachedResourceManager;

    public function __construct(
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever,
        CssSourceInspector $cssSourceInspector,
        CachedResourceManager $cachedResourceManager
    ) {
        $this->taskCachedSourceWebPageRetriever = $taskCachedSourceWebPageRetriever;
        $this->cssSourceInspector = $cssSourceInspector;
        $this->cachedResourceManager = $cachedResourceManager;
    }

    /**
     * @param Task $task
     *
     * @return string[]
     */
    public function find(Task $task): array
    {
        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);
        $webPageUrl = (string) $webPage->getBaseUrl();

        $webPageStylesheetUrls = $this->cssSourceInspector->findStylesheetUrls($webPage);

        /* @var Source[] $sources */
        $sources = $task->getSources();

        $sourceImportUrls = [];

        foreach ($sources as $source) {
            $isStylesheetSource = in_array($source->getUrl(), $webPageStylesheetUrls);

            if ($source->isCachedResource() && $isStylesheetSource) {
                $cachedResource = $this->cachedResourceManager->find($source->getValue());

                $css = stream_get_contents($cachedResource->getBody());
                $importUrls = $this->cssSourceInspector->findCssImportUrls($css, $webPageUrl);

                $sourceImportUrls = array_merge($sourceImportUrls, $importUrls);
            }
        }

        return array_values(array_unique(array_merge(
            $webPageStylesheetUrls,
            $sourceImportUrls
        )));
    }
}
