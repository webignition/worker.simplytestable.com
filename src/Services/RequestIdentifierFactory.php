<?php

namespace App\Services;

use App\Entity\Task\Task;
use App\Model\RequestIdentifier;

class RequestIdentifierFactory
{
    public function createFromTask(Task $task): RequestIdentifier
    {
        return $this->createFromTaskResource($task, $task->getUrl());
    }

    public function createFromTaskResource(Task $task, string $resourceUrl): RequestIdentifier
    {
        return new RequestIdentifier($resourceUrl, $task->getParameters()->toArray());
    }
}
