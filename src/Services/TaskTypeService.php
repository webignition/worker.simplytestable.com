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
        foreach ($taskTypeProperties as $name => $properties) {
            $taskTypeId = $this->createTaskTypeId($name);

            $childType = empty($properties['child-type'])
                ? null
                : $this->get($properties['child-type']);

            $this->taskTypes[$taskTypeId] = new Type(
                $taskTypeId,
                $properties['selectable'],
                $childType
            );
        }
    }

    public function get(string $name): ?Type
    {
        if (!$this->isValid($name)) {
            return null;
        }

        $taskTypeId = $this->createTaskTypeId($name);

        return $this->taskTypes[$taskTypeId];
    }

    private function isValid(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->taskTypes);
    }

    private function createTaskTypeId(string $name): string
    {
        return trim(strtolower($name));
    }
}
