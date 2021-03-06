<?php

namespace App\Services;

use App\Entity\Task\Task;
use webignition\CssValidatorWrapper\SourceType;
use webignition\IgnoredUrlVerifier\IgnoredUrlVerifier;
use webignition\InternetMediaType\Parser\Parser as ContentTypeParser;
use webignition\ResourceStorage\ResourceStorage;
use webignition\UrlSourceMap\Source;
use webignition\UrlSourceMap\SourceMap;

class UrlSourceMapFactory
{
    private $cachedResourceManager;
    private $resourceStorage;
    private $contentTypeParser;
    private $ignoredUrlVerifier;

    public function __construct(
        CachedResourceManager $cachedResourceManager,
        ResourceStorage $resourceStorage,
        ContentTypeParser $contentTypeParser,
        IgnoredUrlVerifier $ignoredUrlVerifier
    ) {
        $this->cachedResourceManager = $cachedResourceManager;
        $this->resourceStorage = $resourceStorage;
        $this->contentTypeParser = $contentTypeParser;
        $this->ignoredUrlVerifier = $ignoredUrlVerifier;
    }

    public function createForTask(Task $task): SourceMap
    {
        $taskParameters = $task->getParameters();
        $hostsToIgnore = $taskParameters->get('domains-to-ignore') ?? [];

        $sources = new SourceMap();
        $taskSources = $task->getSources();

        foreach ($taskSources as $taskSource) {
            $sourceUri = $taskSource->getUrl();
            $mappedUri = null;

            if ($sourceUri === $task->getUrl()) {
                continue;
            }

            if ($taskSource->isCachedResource()) {
                if ($this->ignoredUrlVerifier->isUrlIgnored($sourceUri, [
                    IgnoredUrlVerifier::EXCLUSIONS_HOSTS => $hostsToIgnore,
                ])) {
                    $sources[$sourceUri] = new Source($sourceUri);

                    continue;
                }

                $requestHash = $taskSource->getValue();

                $cachedResource = $this->cachedResourceManager->find($requestHash);

                /** @noinspection PhpUnhandledExceptionInspection */
                $contentType = $this->contentTypeParser->parse($cachedResource->getContentType());

                $source = $this->resourceStorage->store(
                    $sources,
                    $sourceUri,
                    stream_get_contents($cachedResource->getBody()),
                    $contentType->getSubtype()
                );

                $taskSourceContext = $taskSource->getContext();
                $taskSourceOrigin = $taskSourceContext['origin'] ?? null;

                if (SourceType::TYPE_IMPORT === $taskSourceOrigin) {
                    $sources->offsetSet($sourceUri, new Source(
                        $source->getUri(),
                        $source->getMappedUri(),
                        SourceType::TYPE_IMPORT
                    ));
                }
            } else {
                $sources[$sourceUri] = new Source($sourceUri);
            }
        }

        return $sources;
    }
}
