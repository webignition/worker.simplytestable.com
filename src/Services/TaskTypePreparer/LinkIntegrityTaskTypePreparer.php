<?php

namespace App\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Model\Task\Type;

class LinkIntegrityTaskTypePreparer implements TaskPreparerInterface
{
    public function prepare(Task $task): bool
    {
        return true;
    }

    public function handles(string $taskType): bool
    {
        return Type::TYPE_LINK_INTEGRITY === strtolower($taskType);
    }
}
