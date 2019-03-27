<?php

namespace App\Services;

use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Repository\TaskRepository;
use ReflectionClass;

class TaskService
{
    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    public function __construct(TaskTypeService $taskTypeFactory, TaskRepository $taskRepository)
    {
        $this->taskTypeService = $taskTypeFactory;
        $this->taskRepository = $taskRepository;
    }

    public function create(string $url, TypeInterface $type, string $parameters): Task
    {
        $task = Task::create($type, $url, $parameters);

        /* @var Task $existingTask */
        $existingTask = $this->taskRepository->findOneBy([
            'state' => $task->getState(),
            'type' => (string) $task->getType(),
            'url' => $task->getUrl()
        ]);

        if ($existingTask instanceof Task) {
            $this->setTaskType($existingTask, $type);
            $task = $existingTask;
        }

        return $task;
    }

    public function getById(int $id): ?Task
    {
        /* @var Task $task */
        $task = $this->taskRepository->find($id);
        if (!$task instanceof Task) {
            return null;
        }

        $taskTypeName = $this->taskRepository->getTypeById((int) $task->getId());
        if (empty($taskTypeName)) {
            return null;
        }

        $taskType = $this->taskTypeService->get($taskTypeName);
        if (empty($taskType)) {
            return null;
        }

        $this->setTaskType($task, $taskType);

        return $task;
    }

    private function setTaskType(Task $task, TypeInterface $type)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $reflector = new ReflectionClass(Task::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $property = $reflector->getProperty('type');
        $property->setAccessible(true);
        $property->setValue($task, $type);
        $property->setAccessible(false);
    }

    /**
     * @return int[]
     */
    public function getQueuedTaskIds()
    {
        return $this->taskRepository->getIdsByState(Task::STATE_QUEUED);
    }

    /**
     * @return int
     */
    public function getInCompleteCount()
    {
        return $this->taskRepository->getCountByStates([
            Task::STATE_QUEUED,
            Task::STATE_IN_PROGRESS
        ]);
    }

    public function isCachedResourceRequestHashInUse(int $taskId, string $requestHash): bool
    {
        return $this->taskRepository->isSourceValueInUse($taskId, $requestHash);
    }
}
