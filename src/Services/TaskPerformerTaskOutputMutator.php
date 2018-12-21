<?php

namespace App\Services;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\TaskOutputValues;
use webignition\InternetMediaType\InternetMediaType;

class TaskPerformerTaskOutputMutator
{
    public function mutate(Task $task, TaskOutputValues $taskOutputValues)
    {
        $task->setOutput(Output::create(
            json_encode($taskOutputValues->getContent()),
            new InternetMediaType('application', 'json'),
            $taskOutputValues->getErrorCount(),
            $taskOutputValues->getWarningCount()
        ));
    }
}
