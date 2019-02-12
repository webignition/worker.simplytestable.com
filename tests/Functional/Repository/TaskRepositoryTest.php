<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Repository;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Repository\TaskRepository;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestTaskFactory;
use Doctrine\ORM\EntityManagerInterface;

class TaskRepositoryTest extends AbstractBaseTestCase
{
    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    protected function setUp()
    {
        parent::setUp();

        $entityManager = self::$container->get(EntityManagerInterface::class);
        $this->taskRepository = $entityManager->getRepository(Task::class);
        $this->assertInstanceOf(TaskRepository::class, $this->taskRepository);

        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
    }

    /**
     * @dataProvider getIdsByStateDataProvider
     */
    public function testGetIdsByState(array $taskValuesCollection, string $state, array $expectedTaskIndices)
    {
        $tasks = $this->createTaskCollection($taskValuesCollection);
        $expectedTaskIds = $this->createExpectedTaskIds($tasks, $expectedTaskIndices);
        $taskIds = $this->taskRepository->getIdsByState($state);

        $this->assertEquals($expectedTaskIds, $taskIds);
    }

    public function getIdsByStateDataProvider(): array
    {
        return [
            'no tasks' => [
                'taskValuesCollection' => [],
                'state' => '',
                'expectedTaskIndices' => [],
            ],
            'no matching tasks' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'state' => Task::STATE_QUEUED,
                    ]),
                ],
                'state' => Task::STATE_COMPLETED,
                'expectedTaskIndices' => [],
            ],
            'some matching tasks' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/queued/1',
                        'state' => Task::STATE_QUEUED,
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/completed/1',
                        'state' => Task::STATE_COMPLETED,
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/queued/2',
                        'state' => Task::STATE_QUEUED,
                    ]),
                ],
                'state' => Task::STATE_QUEUED,
                'expectedTaskIndices' => [
                    0, 2,
                ],
            ],
            'all matching tasks' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/queued/1',
                        'state' => Task::STATE_QUEUED,
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/queued/2',
                        'state' => Task::STATE_QUEUED,
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/queued/3',
                        'state' => Task::STATE_QUEUED,
                    ]),
                ],
                'state' => Task::STATE_QUEUED,
                'expectedTaskIndices' => [
                    0, 1, 2,
                ],
            ],
        ];
    }

    /**
     * @dataProvider getIdsWithOutputDataProvider
     */
    public function testGetIdsWithOutput(array $taskValuesCollection, array $expectedTaskIndices)
    {
        $tasks = $this->createTaskCollection($taskValuesCollection);
        $expectedTaskIds = $this->createExpectedTaskIds($tasks, $expectedTaskIndices);
        $taskIds = $this->taskRepository->getIdsWithOutput();

        $this->assertEquals($expectedTaskIds, $taskIds);
    }

    public function getIdsWithOutputDataProvider(): array
    {
        return [
            'no tasks' => [
                'taskValuesCollection' => [],
                'expectedTaskIndices' => [],
            ],
            'no tasks with output' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults(),
                ],
                'expectedTaskIndices' => [],
            ],
            'some tasks with output' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/output/1',
                        'output' => Output::create('output1'),
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults(),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/output/2',
                        'output' => Output::create('output2'),
                    ]),
                ],
                'expectedTaskIndices' => [
                    0, 2,
                ],
            ],
            'all tasks with output' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/output/1',
                        'output' => Output::create('output1'),
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/output/2',
                        'output' => Output::create('output2'),
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/output/3',
                        'output' => Output::create('output3'),
                    ]),
                ],
                'expectedTaskIndices' => [
                    0, 1, 2,
                ],
            ],
        ];
    }

    /**
     * @dataProvider getUnfinishedIdsByMaxStartDateDataProvider
     */
    public function testGetUnfinishedIdsByMaxStartDate(
        array $taskValuesCollection,
        \DateTime $maxStartDate,
        array $expectedTaskIndices
    ) {
        $tasks = $this->createTaskCollection($taskValuesCollection);
        $expectedTaskIds = $this->createExpectedTaskIds($tasks, $expectedTaskIndices);
        $taskIds = $this->taskRepository->getUnfinishedIdsByMaxStartDate($maxStartDate);

        $this->assertEquals($expectedTaskIds, $taskIds);
    }

    public function getUnfinishedIdsByMaxStartDateDataProvider(): array
    {
        return [
            'no tasks' => [
                'taskValuesCollection' => [],
                'maxStartDate' => new \DateTime(),
                'expectedTaskIndices' => [],
            ],
            'one task not of suitable age' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '10 minute',
                    ]),
                ],
                'maxStartDate' => new \DateTime('-11 minute'),
                'expectedTaskIndices' => [],
            ],
            'one task is of suitable age' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '10 minute',
                    ]),
                ],
                'maxStartDate' => new \DateTime(),
                'expectedTaskIndices' => [
                    0
                ],
            ],
            'some tasks of suitable age' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '20 minute',
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/3/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '5 minute',
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '15 minute',
                    ]),
                ],
                'maxStartDate' => new \DateTime('-10 minute'),
                'expectedTaskIndices' => [
                    0, 2,
                ],
            ],
            'all tasks of suitable age' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '20 minute',
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/3/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '5 minute',
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '15 minute',
                    ]),
                ],
                'maxStartDate' => new \DateTime('-3 minute'),
                'expectedTaskIndices' => [
                    0, 1, 2,
                ],
            ],
        ];
    }

    /**
     * @dataProvider getCountByStatesDataProvider
     */
    public function testGetCountByStates(array $taskValuesCollection, array $states, int $expectedCount)
    {
        $this->createTaskCollection($taskValuesCollection);
        $count = $this->taskRepository->getCountByStates($states);

        $this->assertEquals($expectedCount, $count);
    }

    public function getCountByStatesDataProvider(): array
    {
        return [
            'no tasks, no states' => [
                'taskValuesCollection' => [],
                'states' => [],
                'expectedCount' => 0,
            ],
            'has tasks, no states' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/queued/1',
                        'state' => Task::STATE_QUEUED,
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/queued/2',
                        'state' => Task::STATE_QUEUED,
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/cancelled/1',
                        'state' => Task::STATE_CANCELLED,
                    ]),
                ],
                'states' => [],
                'expectedCount' => 0,
            ],
            'queued state match' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/queued/1',
                        'state' => Task::STATE_QUEUED,
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/queued/2',
                        'state' => Task::STATE_QUEUED,
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/cancelled/1',
                        'state' => Task::STATE_CANCELLED,
                    ]),
                ],
                'states' => [
                    Task::STATE_QUEUED
                ],
                'expectedCount' => 2,
            ],
        ];
    }

    /**
     * @dataProvider getTypeByIdHasMatchDataProvider
     */
    public function testGetTypeByIdHasMatch(array $taskValues, string $expectedType)
    {
        $task = $this->testTaskFactory->create($taskValues);
        $type = $this->taskRepository->getTypeById($task->getId());

        $this->assertEquals($expectedType, $type);
    }

    public function getTypeByIdHasMatchDataProvider(): array
    {
        return [
            'html validation' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                ]),
                'expectedType' => TypeInterface::TYPE_HTML_VALIDATION,
            ],
            'css validation' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                ]),
                'expectedType' => TypeInterface::TYPE_CSS_VALIDATION,
            ],
        ];
    }

    public function testGetTypeByIdNoMatch()
    {
        $this->assertNull($this->taskRepository->getTypeById(0));
    }

    /**
     * @param array $taskValuesCollection
     *
     * @return Task[]
     */
    private function createTaskCollection(array $taskValuesCollection): array
    {
        $tasks = [];

        foreach ($taskValuesCollection as $taskValues) {
            $tasks[] = $this->testTaskFactory->create($taskValues);
        }

        return $tasks;
    }

    /**
     * @param Task[] $tasks
     * @param int[]  $expectedTaskIndices
     *
     * @return int[]
     */
    private function createExpectedTaskIds(array $tasks, array $expectedTaskIndices)
    {
        $expectedTaskIds = [];

        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskIndices)) {
                $expectedTaskIds[] = $task->getId();
            }
        }

        return $expectedTaskIds;
    }
}
