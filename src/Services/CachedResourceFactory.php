<?php

namespace App\Services;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use webignition\WebResource\WebPage\WebPage;

class CachedResourceFactory
{
    public function createForTask(string $requestHash, Task $task, WebPage $webPage)
    {
        return CachedResource::create(
            $requestHash,
            $task->getUrl(),
            (string)$webPage->getContentType(),
            $webPage->getContent()
        );
    }
}
