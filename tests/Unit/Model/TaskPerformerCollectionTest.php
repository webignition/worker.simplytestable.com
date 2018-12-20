<?php

namespace App\Tests\Unit\Model;

use App\Model\TaskPerformerCollection;
use App\Services\TaskTypePerformer\TaskPerformerInterface;

class TaskPerformerCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param array $performers
     * @param array $expectedPerformerOrder
     */
    public function testCreate(array $performers, array $expectedPerformerOrder)
    {
        $taskPerformerCollection = new TaskPerformerCollection($performers);

        $expectedSortedPerformerHashes = [];
        foreach ($expectedPerformerOrder as $performerId) {
            $expectedSortedPerformerHashes[] = spl_object_hash($performers[$performerId]);
        }

        $sortedPerformerHashes = [];
        foreach ($taskPerformerCollection as $performerIndex => $taskPerformer) {
            $sortedPerformerHashes[] = spl_object_hash($taskPerformer);
        }

        $this->assertEquals($expectedSortedPerformerHashes, $sortedPerformerHashes);
    }

    public function createDataProvider(): array
    {
        return [
            'single' => [
                'taskPerformers' => [
                    'A' => $this->createTaskPerformer(10),
                ],
                'expectedPerformerOrder' => ['A'],
            ],
            'multiple, unique priorities' => [
                'taskPerformers' => [
                    'A' => $this->createTaskPerformer(9),
                    'B' => $this->createTaskPerformer(1),
                    'C' => $this->createTaskPerformer(3),
                    'D' => $this->createTaskPerformer(4),
                    'E' => $this->createTaskPerformer(5),
                ],
                'expectedPerformerOrder' => ['A', 'E', 'D', 'C', 'B'],
            ],
            'multiple, non-unique priorities' => [
                'taskPerformers' => [
                    'A' => $this->createTaskPerformer(10),
                    'B' => $this->createTaskPerformer(5),
                    'C' => $this->createTaskPerformer(20),
                    'D' => $this->createTaskPerformer(1),
                    'E' => $this->createTaskPerformer(10),
                ],
                'expectedPerformerOrder' => ['C', 'A', 'E', 'B', 'D'],
            ],
        ];
    }

    private function createTaskPerformer(int $priority): TaskPerformerInterface
    {
        $performer = \Mockery::mock(TaskPerformerInterface::class);
        $performer
            ->shouldReceive('getPriority')
            ->andReturn($priority);

        return $performer;
    }
}
