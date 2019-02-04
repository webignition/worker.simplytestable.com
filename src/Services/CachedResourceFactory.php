<?php

namespace App\Services;

use App\Entity\CachedResource;
use webignition\WebResourceInterfaces\WebResourceInterface;

class CachedResourceFactory
{
    public function create(string $requestHash, string $resourceUrl, WebResourceInterface $resource)
    {
        return CachedResource::create(
            $requestHash,
            $resourceUrl,
            (string) $resource->getContentType(),
            $resource->getContent()
        );
    }
}
