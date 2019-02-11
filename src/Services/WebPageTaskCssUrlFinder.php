<?php

namespace App\Services;

use App\Entity\Task\Task;
use App\Model\CssSourceUrl;
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
        $cssSourceUrls = [];

        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);
        $webPageUrl = (string) $webPage->getBaseUrl();

        $webPageStylesheetUrls = $this->cssSourceInspector->findStylesheetUrls($webPage);

        foreach ($webPageStylesheetUrls as $webPageStylesheetUrl) {
            $cssSourceUrls[$webPageStylesheetUrl] = new CssSourceUrl(
                $webPageStylesheetUrl,
                CssSourceUrl::TYPE_RESOURCE
            );
        }

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

                foreach ($importUrls as $importUrl) {
                    if (!array_key_exists($importUrl, $cssSourceUrls)) {
                        $cssSourceUrls[$importUrl] = new CssSourceUrl($importUrl, CssSourceUrl::TYPE_IMPORT);
                    }
                }
            }
        }

        return array_values($cssSourceUrls);
    }
}
