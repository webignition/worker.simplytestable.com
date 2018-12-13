<?php

namespace App\Services\TaskTypePreparer;

use App\Model\TaskPreparerCollection;

class Factory
{
    /**
     * @var TaskPreparerInterface[]
     */
    private $taskPreparers = [];

    public function __construct(array $taskPreparers = [])
    {
        $this->taskPreparers = $taskPreparers;
    }

    public function getPreparer(string $taskType): ?TaskPreparerInterface
    {
        foreach ($this->taskPreparers as $taskPreparer) {
            if ($taskPreparer->handles($taskType)) {
                return $taskPreparer;
            }
        }

        return null;
    }

    public function getPreparers(string $taskType): TaskPreparerCollection
    {
        $taskPreparers = [];

        foreach ($this->taskPreparers as $taskPreparer) {
            if ($taskPreparer->handles($taskType)) {
                $taskPreparers[] = $taskPreparer;
            }
        }

        return new TaskPreparerCollection($taskPreparers);
    }
}
