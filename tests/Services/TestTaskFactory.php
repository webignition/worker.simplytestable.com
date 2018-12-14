<?php

namespace App\Tests\Services;

use App\Model\Task\TypeInterface;
use App\Services\TaskTypeService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Services\TaskService;

class TestTaskFactory
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TypeInterface::TYPE_HTML_VALIDATION;
    const DEFAULT_TASK_STATE = Task::STATE_QUEUED;

    /**
     * @var array
     */
    private static $defaultTaskValues = [
        'url' => self::DEFAULT_TASK_URL,
        'type' => self::DEFAULT_TASK_TYPE,
        'parameters' => self::DEFAULT_TASK_PARAMETERS,
        'state' => self::DEFAULT_TASK_STATE,
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        TaskTypeService $taskTypeService
    ) {
        $this->entityManager = $entityManager;
        $this->taskService = $taskService;
        $this->taskTypeService = $taskTypeService;
    }


    /**
     * @param array $taskValues
     *
     * @return array
     */
    public static function createTaskValuesFromDefaults(array $taskValues = [])
    {
        return array_merge(self::$defaultTaskValues, $taskValues);
    }


    /**
     * @param string[] $taskValues
     *
     * @return Task
     *
     * @throws \Exception
     */
    public function create($taskValues)
    {
        if (!isset($taskValues['parameters'])) {
            $taskValues['parameters'] = '';
        }

        $task = $this->taskService->create(
            $taskValues['url'],
            $this->taskTypeService->get($taskValues['type']),
            $taskValues['parameters']
        );

        if ($taskValues['state'] != self::DEFAULT_TASK_STATE) {
            $task->setState($taskValues['state']);
        }

        if (isset($taskValues['age'])) {
            $task->setStartDateTime(new \DateTime('-' . $taskValues['age']));
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }
}
