<?php

namespace App\Tests\Unit\Model;

use App\Model\TaskPreparerCollection;
use App\Services\TaskTypePreparer\TaskPreparerInterface;

class TaskPreparerCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param array $preparers
     * @param array $expectedPreparerOrder
     */
    public function testCreate(array $preparers, array $expectedPreparerOrder)
    {
        $taskPreparerCollection = new TaskPreparerCollection($preparers);

        $expectedSortedPreparerHashes = [];
        foreach ($expectedPreparerOrder as $preparerId) {
            $expectedSortedPreparerHashes[] = spl_object_hash($preparers[$preparerId]);
        }

        $sortedPreparerHashes = [];
        foreach ($taskPreparerCollection as $preparerIndex => $taskPreparer) {
            $sortedPreparerHashes[] = spl_object_hash($taskPreparer);
        }

        $this->assertEquals($expectedSortedPreparerHashes, $sortedPreparerHashes);
    }

    public function createDataProvider(): array
    {
        return [
            'single' => [
                'taskPreparers' => [
                    'A' => $this->createTaskPreparer(10),
                ],
                'expectedPreparerOrder' => ['A'],
            ],
            'multiple, unique priorities' => [
                'taskPreparers' => [
                    'A' => $this->createTaskPreparer(9),
                    'B' => $this->createTaskPreparer(1),
                    'C' => $this->createTaskPreparer(3),
                    'D' => $this->createTaskPreparer(4),
                    'E' => $this->createTaskPreparer(5),
                ],
                'expectedPreparerOrder' => ['A', 'E', 'D', 'C', 'B'],
            ],
            'multiple, non-unique priorities' => [
                'taskPreparers' => [
                    'A' => $this->createTaskPreparer(10),
                    'B' => $this->createTaskPreparer(5),
                    'C' => $this->createTaskPreparer(20),
                    'D' => $this->createTaskPreparer(1),
                    'E' => $this->createTaskPreparer(10),
                ],
                'expectedPreparerOrder' => ['C', 'A', 'E', 'B', 'D'],
            ],
        ];
    }

    private function createTaskPreparer(int $priority): TaskPreparerInterface
    {
        $preparer = \Mockery::mock(TaskPreparerInterface::class);
        $preparer
            ->shouldReceive('getPriority')
            ->andReturn($priority);

        return $preparer;
    }
}
