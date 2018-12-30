<?php

namespace App\Services;

use App\Model\Task\Type;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Repository\TaskRepository;
use ReflectionClass;

class TaskService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    public function __construct(EntityManagerInterface $entityManager, TaskTypeService $taskTypeFactory)
    {
        $this->entityManager = $entityManager;
        $this->taskTypeService = $taskTypeFactory;

        $this->taskRepository = $entityManager->getRepository(Task::class);
    }

    public function create(string $url, Type $type, string $parameters): Task
    {
        $task = Task::create($type, $url, $parameters);

        /* @var Task $existingTask */
        $existingTask = $this->taskRepository->findOneBy([
            'state' => $task->getState(),
            'type' => (string) $task->getType(),
            'url' => $task->getUrl()
        ]);

        if ($existingTask) {
            $this->setTaskType($existingTask, $type);
        }

        return $existingTask ?? $task;
    }

    /**
     * @param int $id
     *
     * @return Task
     *
     */
    public function getById($id)
    {
        /* @var $task Task */
        $task = $this->taskRepository->find($id);

        if (empty($task)) {
            return null;
        }

        $taskTypeName = $this->taskRepository->getTypeById($task->getId());
        if (empty($taskTypeName)) {
            return null;
        }

        $this->setTaskType($task, $this->taskTypeService->get($taskTypeName));

        return $task;
    }

    private function setTaskType(Task $task, Type $type)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $reflector = new ReflectionClass(Task::class);
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
