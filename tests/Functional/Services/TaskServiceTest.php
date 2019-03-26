<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Model\Task\Parameters;
use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Tests\Services\TaskTypeRetriever;
use App\Tests\Services\TestTaskFactory;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Services\TaskService;
use App\Tests\Functional\AbstractBaseTestCase;

/**
 * @group TaskService
 */
class TaskServiceTest extends AbstractBaseTestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TypeInterface::TYPE_HTML_VALIDATION;
    const DEFAULT_TASK_STATE = Task::STATE_QUEUED;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    /**
     * @var TaskTypeRetriever
     */
    private $taskTypeRetriever;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskService = self::$container->get(TaskService::class);
        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
        $this->taskTypeRetriever = self::$container->get(TaskTypeRetriever::class);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(string $url, string $taskTypeName, string $parameters)
    {
        $task = $this->taskService->create($url, $this->taskTypeRetriever->retrieve($taskTypeName), $parameters);

        $parametersArray = json_decode($parameters, true) ?? [];

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals(Task::STATE_QUEUED, $task->getState());
        $this->assertEquals($url, $task->getUrl());
        $this->assertEquals(strtolower($taskTypeName), strtolower($task->getType()));
        $this->assertEquals(new Parameters($parametersArray, $url), $task->getParameters());
    }

    public function createDataProvider(): array
    {
        return [
            'html validation default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TypeInterface::TYPE_HTML_VALIDATION,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
            'css validation default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TypeInterface::TYPE_CSS_VALIDATION,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
            'link integrity default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TypeInterface::TYPE_LINK_INTEGRITY,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
            'url discovery default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TypeInterface::TYPE_URL_DISCOVERY,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
            'url discovery with parameters' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TypeInterface::TYPE_URL_DISCOVERY,
                'parameters' => json_encode([
                    'foo' => 'bar',
                ]),
            ],
        ];
    }

    public function testCreateUsesExistingMatchingTask()
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $existingTask = $this->taskService->create(
            self::DEFAULT_TASK_URL,
            $this->taskTypeRetriever->retrieve(TypeInterface::TYPE_HTML_VALIDATION),
            ''
        );

        $entityManager->persist($existingTask);
        $entityManager->flush();

        $newTask = $this->taskService->create(
            self::DEFAULT_TASK_URL,
            $this->taskTypeRetriever->retrieve(TypeInterface::TYPE_HTML_VALIDATION),
            ''
        );
        $this->assertEquals($existingTask->getId(), $newTask->getId());
    }

    public function testGetById()
    {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
        $id = (int) $task->getId();

        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $entityManager->detach($task);

        $retrievedTask = $this->taskService->getById($id);

        if ($retrievedTask instanceof Task) {
            $this->assertEquals($id, $retrievedTask->getId());
            $this->assertInstanceOf(Type::class, $retrievedTask->getType());
        } else {
            $this->fail('Task was retrieved by known id');
        }
    }

    public function testGetIncompleteCount()
    {
        $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'state' => Task::STATE_QUEUED,
            'type' => TypeInterface::TYPE_HTML_VALIDATION,
        ]));

        $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'state' => Task::STATE_IN_PROGRESS,
            'type' => TypeInterface::TYPE_CSS_VALIDATION,
        ]));

        $this->assertEquals(2, $this->taskService->getInCompleteCount());
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
