<?php

namespace App\Services\TaskTypePreparer;

use App\Entity\Task\Task;

interface TaskPreparerInterface
{
    public function prepare(Task $task): bool;
    public function handles(string $taskType): bool;
}