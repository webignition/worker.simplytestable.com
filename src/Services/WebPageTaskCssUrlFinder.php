<?php

namespace App\Services;

use App\Entity\Task\Task;
use App\Exception\UnableToPerformTaskException;
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
     * @return CssSourceUrl[]
     *
     * @throws UnableToPerformTaskException
     */
    public function find(Task $task): array
    {
        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);

        if (empty($webPage)) {
            throw new UnableToPerformTaskException();
        }

        $webPageUrl = (string) $webPage->getBaseUrl();

        $cssSourceUrls = $this->cssSourceInspector->findStylesheetUrls($webPage);

        /* @var Source[] $sources */
        $sources = $task->getSources();

        $sourceImportUrls = [];

        foreach ($sources as $source) {
            $isStylesheetSource = array_key_exists($source->getUrl(), $cssSourceUrls);

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

        return $cssSourceUrls;
    }
}
