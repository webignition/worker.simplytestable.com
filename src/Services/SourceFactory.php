<?php

namespace App\Services;

use App\Entity\CachedResource;
use App\Model\Source;

class SourceFactory
{
    public function fromCachedResource(CachedResource $cachedResource, array $context = []): Source
    {
        return new Source(
            $cachedResource->getUrl(),
            Source::TYPE_CACHED_RESOURCE,
            $cachedResource->getRequestHash(),
            $context
        );
    }

    public function createHttpFailedSource(string $url, int $statusCode, array $context = []): Source
    {
        return $this->createUnavailableSource($url, Source::FAILURE_TYPE_HTTP, $statusCode, $context);
    }

    public function createCurlFailedSource(string $url, int $curlCode): Source
    {
        return $this->createUnavailableSource($url, Source::FAILURE_TYPE_CURL, $curlCode);
    }

    public function createUnknownFailedSource(string $url): Source
    {
        return $this->createUnavailableSource($url, Source::FAILURE_TYPE_UNKNOWN, 0);
    }

    public function createInvalidSource(string $url, string $message): Source
    {
        return new Source(
            $url,
            Source::TYPE_INVALID,
            'invalid' . ':' . $message
        );
    }

    private function createUnavailableSource(
        string $url,
        string $failureType,
        int $failureCode,
        array $context = []
    ): Source {
        return new Source(
            $url,
            Source::TYPE_UNAVAILABLE,
            $failureType . ':' . $failureCode,
            $context
        );
    }
}
