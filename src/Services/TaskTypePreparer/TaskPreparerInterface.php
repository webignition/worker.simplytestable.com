<?php

namespace App\Services\TaskTypePreparer;

use App\Entity\Task\Task;

interface TaskPreparerInterface
{
    public function prepare(Task $task);
    public function handles(string $taskType): bool;
    public function getPriority(): int;
}
