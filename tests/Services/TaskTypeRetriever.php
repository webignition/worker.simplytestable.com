<?php

namespace App\Tests\Services;

use App\Model\Task\TypeInterface;
use App\Services\TaskTypeService;

class TaskTypeRetriever
{
    private $taskTypeService;

    public function __construct(TaskTypeService $taskTypeService)
    {
        $this->taskTypeService = $taskTypeService;
    }

    public function retrieve(string $name): TypeInterface
    {
        $type = $this->taskTypeService->get($name);

        if (!$type instanceof TypeInterface) {
            throw new \InvalidArgumentException('Invalid task type "' . $name . '"');
        }

        return $type;
    }
}
