<?php

namespace App\Services;

use App\Entity\Task\Task;
use GuzzleHttp\Psr7\Uri;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResourceProperties;

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
//                    $webPage = new WebPage(WebResourceProperties::create([
//                        WebResourceProperties::ARG_CONTENT_TYPE => new InternetMediaType('text', 'html'),
//                        WebResourceProperties::ARG_CONTENT => stream_get_contents($cachedResource->getBody()),
//                        WebResourceProperties::ARG_URI => $task->getUrl(),
//                    ]));

                    /* @var WebPage $webPage */
                    /** @noinspection PhpUnhandledExceptionInspection */
                    $webPage = WebPage::createFromContent(stream_get_contents($cachedResource->getBody()));
                    $webPage = $webPage->setUri(new Uri($task->getUrl()));

                    return $webPage;
                }
            }
        }

        return null;
    }
}
