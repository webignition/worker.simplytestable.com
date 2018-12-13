<?php

namespace App\Tests\Unit\Services\TaskTypePreparer;

use App\Model\Task\Type;
use App\Model\TaskPreparerCollection;
use App\Services\TaskTypePreparer\Factory;
use App\Services\TaskTypePreparer\TaskPreparerInterface;

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getPreparersDataProvider
     *
     * @param array $preparers
     * @param string $taskType
     * @param array $expectedPreparers
     */
    public function testGetPreparers(array $preparers, string $taskType, array $expectedPreparers)
    {
        $factory = new Factory($preparers);

        $taskPreparerCollection = $factory->getPreparers($taskType);
        $this->assertInstanceOf(TaskPreparerCollection::class, $taskPreparerCollection);

        $expectedPreparerHashes = [];
        foreach ($expectedPreparers as $preparerId) {
            $expectedPreparerHashes[] = spl_object_hash($preparers[$preparerId]);
        }

        $preparerHashes = [];
        foreach ($taskPreparerCollection as $preparerIndex => $taskPreparer) {
            $preparerHashes[] = spl_object_hash($taskPreparer);
        }

        $this->assertEquals($expectedPreparerHashes, $preparerHashes);
    }

    public function getPreparersDataProvider(): array
    {
        return [
            'no preparers' => [
                'preparers' => [],
                'taskType' => Type::TYPE_HTML_VALIDATION,
                'expectedPreparers' => [],
            ],
            'single preparer, no match' => [
                'preparers' => [
                    'A' => $this->createTaskPreparer(Type::TYPE_HTML_VALIDATION),
                ],
                'taskType' => Type::TYPE_CSS_VALIDATION,
                'expectedPreparers' => [],
            ],
            'multiple preparers, none match' => [
                'preparers' => [
                    'A' => $this->createTaskPreparer(Type::TYPE_HTML_VALIDATION),
                    'B' => $this->createTaskPreparer(Type::TYPE_CSS_VALIDATION),
                    'C' => $this->createTaskPreparer(Type::TYPE_LINK_INTEGRITY),
                ],
                'taskType' => Type::TYPE_URL_DISCOVERY,
                'expectedPreparers' => [],
            ],
            'single preparer, is match' => [
                'preparers' => [
                    'A' => $this->createTaskPreparer(Type::TYPE_HTML_VALIDATION),
                ],
                'taskType' => Type::TYPE_HTML_VALIDATION,
                'expectedPreparers' => ['A'],
            ],
            'multiple preparers, some match' => [
                'preparers' => [
                    'A' => $this->createTaskPreparer(Type::TYPE_HTML_VALIDATION),
                    'B' => $this->createTaskPreparer(Type::TYPE_CSS_VALIDATION),
                    'C' => $this->createTaskPreparer(Type::TYPE_HTML_VALIDATION),
                    'D' => $this->createTaskPreparer(Type::TYPE_LINK_INTEGRITY),
                    'E' => $this->createTaskPreparer(Type::TYPE_LINK_INTEGRITY_SINGLE_URL),
                ],
                'taskType' => Type::TYPE_HTML_VALIDATION,
                'expectedPreparers' => ['A', 'C'],
            ],
            'multiple preparers, all match' => [
                'preparers' => [
                    'A' => $this->createTaskPreparer(Type::TYPE_HTML_VALIDATION),
                    'B' => $this->createTaskPreparer(Type::TYPE_HTML_VALIDATION),
                    'C' => $this->createTaskPreparer(Type::TYPE_HTML_VALIDATION),
                ],
                'taskType' => Type::TYPE_HTML_VALIDATION,
                'expectedPreparers' => ['A', 'B', 'C'],
            ],
        ];
    }

    private function createTaskPreparer(string $handlesTaskType): TaskPreparerInterface
    {
        $taskTypes = [
            Type::TYPE_HTML_VALIDATION,
            Type::TYPE_CSS_VALIDATION,
            Type::TYPE_LINK_INTEGRITY,
            Type::TYPE_URL_DISCOVERY,
        ];

        $preparer = \Mockery::mock(TaskPreparerInterface::class);
        $preparer
            ->shouldReceive('getPriority')
            ->andReturn(1);

        foreach ($taskTypes as $taskType) {
            $handlesReturnValue = $handlesTaskType === $taskType;

            $preparer
                ->shouldReceive('handles')
                ->with($taskType)
                ->andReturn($handlesReturnValue);
        }

        return $preparer;
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
