<?php

namespace App\Services\TaskTypePerformer;

use App\Entity\Task\Output as TaskOutput;
use App\Entity\Task\Task;
use App\Model\TaskTypePerformer\Response as TaskTypePerformerResponse;
use webignition\InternetMediaType\InternetMediaType;

abstract class TaskTypePerformer
{
    const OUTPUT_STARTING_STATE = 'taskoutput-queued';

    /**
     * @var TaskTypePerformerResponse
     */
    protected $response = null;

    /**
     * @param Task $task
     *
     * @return TaskTypePerformerResponse
     */
    public function perform(Task $task)
    {
        $this->response = new TaskTypePerformerResponse();

        $rawOutput = $this->execute($task);

        $output = new TaskOutput();
        $output->setOutput($rawOutput);
        $output->setContentType($this->getOutputContentType());
        $output->setErrorCount($this->response->getErrorCount());
        $output->setWarningCount($this->response->getWarningCount());

        $this->response->setTaskOutput($output);

        return $this->response;
    }

    /**
     * @param Task $task
     *
     * @return string
     */
    abstract protected function execute(Task $task);

    /**
     * @return InternetMediaType
     */
    abstract protected function getOutputContentType();
}