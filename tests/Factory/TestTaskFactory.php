<?php

namespace App\Tests\Factory;

use App\Model\Task\TypeInterface;
use Doctrine\ORM\ORMException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use App\Entity\Task\Task;
use App\Entity\TimePeriod;
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
     */
    public function create($taskValues)
    {
        $taskService = null;
        $stateService = null;
        $entityManager = null;

        try {
            $taskService = $this->container->get(TaskService::class);
            $entityManager = $this->container->get('doctrine.orm.entity_manager');
        } catch (NotFoundExceptionInterface $e) {
        } catch (ContainerExceptionInterface $e) {
        }

        if (!isset($taskValues['parameters'])) {
            $taskValues['parameters'] = '';
        }

        $task = $taskService->create($taskValues['url'], $taskValues['type'], $taskValues['parameters']);

        if ($taskValues['state'] != self::DEFAULT_TASK_STATE) {
            $task->setState($taskValues['state']);
        }

        if (isset($taskValues['age'])) {
            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime('-' . $taskValues['age']));

            $task->setTimePeriod($timePeriod);
        }

        try {
            $entityManager->persist($task);
            $entityManager->flush();
        } catch (ORMException $e) {
        }

        return $task;
    }
}
