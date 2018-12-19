<?php

namespace App\Services;

use App\Entity\Task\Task;
use App\Model\RequestIdentifier;

class RequestIdentifierFactory
{
    public function createFromTask(Task $task)
    {
        return new RequestIdentifier($task->getUrl(), $task->getParameters()->toArray());
    }
}
