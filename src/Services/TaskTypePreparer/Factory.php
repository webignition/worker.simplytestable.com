<?php

namespace App\Services\TaskTypePreparer;

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
}
