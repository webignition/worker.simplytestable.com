<?php

namespace App\Services;

use App\Model\Task\DecoratedTask;
use App\Model\Task\Type;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Repository\TaskRepository;

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

        $this->setQueued($task);

        $existingTask = $this->taskRepository->findOneBy([
            'state' => $task->getState(),
            'type' => $task->getType(),
            'url' => $task->getUrl()
        ]);

        return $existingTask
            ? new DecoratedTask($existingTask, $type)
            : $task;
    }

    /**
     * @param int $id
     *
     * @return Task
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

        return new DecoratedTask($task, $this->taskTypeService->get($taskTypeName));
    }

    /**
     * @return int[]
     */
    public function getQueuedTaskIds()
    {
        return $this->taskRepository->getIdsByState(Task::STATE_QUEUED);
    }

    public function setQueued(Task $task)
    {
        $task->setState(Task::STATE_QUEUED);
    }

    /**
     * @param Task $task
     */
    public function cancel(Task $task)
    {
        $taskStateName = $task->getState();

        $isCancelled = Task::STATE_CANCELLED === $taskStateName;
        $isCompleted = Task::STATE_COMPLETED === $taskStateName;

        if (!($isCancelled || $isCompleted)) {
            $task->setState(Task::STATE_CANCELLED);

            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }
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
}
