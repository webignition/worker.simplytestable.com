<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Task;

interface TaskPerformerInterface
{
    public function prepare(Task $task): bool;
    public function handles(string $taskType): bool;
    public function getPriority(): int;
}
