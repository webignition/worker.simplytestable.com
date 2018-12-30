<?php

namespace App\Services;

use App\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;

class TaskCachedSourceWebPageRetriever
{
    private $cachedResourceManager;

    public function __construct(CachedResourceManager $cachedResourceManager)
    {
        $this->cachedResourceManager = $cachedResourceManager;
    }

    public function retrieve(Task $task): ?WebPage
    {
        $sources = $task->getSources();

        if (!empty($sources)) {
            $primarySource = $sources[$task->getUrl()] ?? null;

            if ($primarySource && $primarySource->isCachedResource()) {
                $requestHash = $primarySource->getValue();
                $cachedResource = $this->cachedResourceManager->find($requestHash);

                if ($cachedResource) {
                    /* @var WebPage $webPage */
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $webPage = WebPage::createFromContent(stream_get_contents($cachedResource->getBody()));

                    return $webPage;
                }
            }
        }

        return null;
    }
}
