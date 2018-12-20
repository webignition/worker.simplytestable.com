<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Task;
use App\Model\TaskTypePerformer\Response as TaskTypePerformerResponse;

interface TaskTypePerformerInterface
{
    public function perform(Task $task): ?TaskTypePerformerResponse;
}
