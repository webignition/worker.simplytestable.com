<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Task;

interface TaskTypePerformerInterface
{
    public function perform(Task $task);
}
