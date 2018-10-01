<?php

namespace App\Services;

use App\Model\Task\Type;

class TaskTypeService
{
    /**
     * @var Type[]
     */
    private $taskTypes;

    public function __construct(array $taskTypeProperties)
    {
        foreach ($taskTypeProperties as $taskTypeName => $properties) {
            $taskTypeId = strtolower($taskTypeName);

            $this->taskTypes[$taskTypeId] = new Type($taskTypeId);
        }
    }

    public function get(string $name): ?Type
    {
        if ($this->isValid($name)) {
            return null;
        }

        return new Type(strtolower($name));
    }

    public function isValid(string $name): bool
    {
        return array_keys($this->taskTypes, strtolower($name));
    }
}
