<?php

namespace App\Services;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use GuzzleHttp\Psr7\Uri;
use webignition\InternetMediaType\Parser\ParseException;
use webignition\InternetMediaType\Parser\Parser as ContentTypeParser;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResource\WebPage\WebPage;

class TaskCachedSourceWebPageRetriever
{
    private $cachedResourceManager;
    private $contentTypeParser;

    public function __construct(
        CachedResourceManager $cachedResourceManager,
        ContentTypeParser $contentTypeParser
    ) {
        $this->cachedResourceManager = $cachedResourceManager;
        $this->contentTypeParser = $contentTypeParser;
    }

    public function retrieve(Task $task): ?WebPage
    {
        $sources = $task->getSources();

        if (empty($sources)) {
            return null;
        }

        $primarySource = $sources[$task->getUrl()] ?? null;

        if (empty($primarySource) || ($primarySource instanceof Source && !$primarySource->isCachedResource())) {
            return null;
        }

        $requestHash = $primarySource->getValue();
        $cachedResource = $this->cachedResourceManager->find($requestHash);

        if (empty($cachedResource)) {
            return null;
        }

        $contentType = $this->getCachedResourceContentType($cachedResource);

        if (empty($contentType)) {
            return null;
        }

        /* @var WebPage $webPage */
        /** @noinspection PhpUnhandledExceptionInspection */
        $webPage = WebPage::createFromContent(
            (string) stream_get_contents($cachedResource->getBody())
        );

        $webPage = $webPage->setContentType($contentType);
        $webPage = $webPage->setUri(new Uri($task->getUrl()));

        return $webPage;
    }

    private function getCachedResourceContentType(CachedResource $cachedResource): ?InternetMediaTypeInterface
    {
        $contentTypeString = $cachedResource->getContentType();
        $contentType = null;

        try {
            $contentType = $this->contentTypeParser->parse($contentTypeString);
        } catch (ParseException $parseException) {
            // Do nothing
        }

        if (empty($contentType)) {
            return null;
        }

        if (!WebPage::models($contentType)) {
            return null;
        }

        return $contentType;
    }
}
