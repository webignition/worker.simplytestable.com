<?php

namespace Tests\WorkerBundle\Factory;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
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
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create($taskValues)
    {
        $taskTypeService = null;
        $taskService = null;
        $stateService = null;
        $entityManager = null;

        try {
            $taskTypeService = $this->container->get(TaskTypeService::class);
            $taskService = $this->container->get(TaskService::class);
            $stateService = $this->container->get(StateService::class);
            $entityManager = $this->container->get('doctrine.orm.entity_manager');
        } catch (NotFoundExceptionInterface $e) {
        } catch (ContainerExceptionInterface $e) {
        }

        if (!isset($taskValues['parameters'])) {
            $taskValues['parameters'] = '';
        }

        $taskType = $taskTypeService->fetch($taskValues['type']);
        $task = $taskService->create($taskValues['url'], $taskType, $taskValues['parameters']);

        if ($taskValues['state'] != self::DEFAULT_TASK_STATE) {
            $task->setState($stateService->fetch($taskValues['state']));
        }

        if (isset($taskValues['age'])) {
            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime('-' . $taskValues['age']));

            $task->setTimePeriod($timePeriod);
        }

        $entityManager->persist($task);
        $entityManager->flush();

        return $task;
    }
}
