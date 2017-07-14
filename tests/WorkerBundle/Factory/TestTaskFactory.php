<?php

namespace Tests\WorkerBundle\Factory;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;

class TestTaskFactory
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TaskTypeService::HTML_VALIDATION_NAME;
    const DEFAULT_TASK_STATE = TaskService::TASK_STARTING_STATE;

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
     * @var TaskService
     */
    private $taskService;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param TaskService $taskService
     * @param TaskTypeService $taskTypeService
     * @param StateService $stateService
     * @param EntityManager $entityManager
     */
    public function __construct(
        TaskService $taskService,
        TaskTypeService $taskTypeService,
        StateService $stateService,
        EntityManager $entityManager
    ) {
        $this->taskService = $taskService;
        $this->taskTypeService = $taskTypeService;
        $this->stateService = $stateService;
        $this->entityManager = $entityManager;
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
     */
    public function create($taskValues)
    {
        if (!isset($taskValues['parameters'])) {
            $taskValues['parameters'] = '';
        }

        $taskType = $this->taskTypeService->fetch($taskValues['type']);
        $task = $this->taskService->create($taskValues['url'], $taskType, $taskValues['parameters']);

        if ($taskValues['state'] != self::DEFAULT_TASK_STATE) {
            $task->setState($this->stateService->fetch($taskValues['state']));
        }

        if (isset($taskValues['age'])) {
            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime('-' . $taskValues['age']));

            $task->setTimePeriod($timePeriod);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }
}
