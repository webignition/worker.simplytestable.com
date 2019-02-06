<?php

namespace App\Services;

use App\Entity\Task\Task;
use webignition\InternetMediaType\Parser\Parser as ContentTypeParser;
use webignition\ResourceStorage\ResourceStorage;
use webignition\UrlSourceMap\Source;
use webignition\UrlSourceMap\SourceMap;

class UrlSourceMapFactory
{
    private $cachedResourceManager;
    private $resourceStorage;
    private $contentTypeParser;

    public function __construct(
        CachedResourceManager $cachedResourceManager,
        ResourceStorage $resourceStorage,
        ContentTypeParser $contentTypeParser
    ) {
        $this->cachedResourceManager = $cachedResourceManager;
        $this->resourceStorage = $resourceStorage;
        $this->contentTypeParser = $contentTypeParser;
    }

    public function createForTask(Task $task): SourceMap
    {
        $sources = new SourceMap();
        $taskSources = $task->getSources();

        foreach ($taskSources as $taskSource) {
            $sourceUri = $taskSource->getUrl();
            $mappedUri = null;

            if ($sourceUri !== $task->getUrl()) {
                if ($taskSource->isCachedResource()) {
                    $requestHash = $taskSource->getValue();

                    $cachedResource = $this->cachedResourceManager->find($requestHash);

                    /** @noinspection PhpUnhandledExceptionInspection */
                    $contentType = $this->contentTypeParser->parse($cachedResource->getContentType());

                    $this->resourceStorage->store(
                        $sources,
                        $sourceUri,
                        stream_get_contents($cachedResource->getBody()),
                        $contentType->getSubtype()
                    );
                } else {
                    $sources[$sourceUri] = new Source($sourceUri);
                }
            }
        }

        return $sources;
    }
}
