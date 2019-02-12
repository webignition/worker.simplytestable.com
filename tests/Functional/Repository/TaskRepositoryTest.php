<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Repository;

use App\Entity\Task\Task;
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
    public function testGetIdsByState(array $taskValuesCollection, string $state, array $expectedTaskIndicies)
    {
        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($taskValuesCollection as $taskValues) {
            $tasks[] = $this->testTaskFactory->create($taskValues);
        }

        $expectedTaskIds = [];

        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskIndicies)) {
                $expectedTaskIds[] = $task->getId();
            }
        }

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
}
