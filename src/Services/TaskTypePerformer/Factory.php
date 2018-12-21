<?php

namespace App\Services\TaskTypePerformer;

use App\Model\TaskPerformerCollection;

class Factory
{
    /**
     * @var TaskPerformerInterface[]
     */
    private $taskPerformers = [];

    public function __construct(array $taskPerformers = [])
    {
        $this->taskPerformers = $taskPerformers;
    }

    public function getPerformers(string $taskType): TaskPerformerCollection
    {
        $taskPerformers = [];

        foreach ($this->taskPerformers as $taskPerformer) {
            if ($taskPerformer->handles($taskType)) {
                $taskPerformers[] = $taskPerformer;
            }
        }

        return new TaskPerformerCollection($taskPerformers);
    }
}
