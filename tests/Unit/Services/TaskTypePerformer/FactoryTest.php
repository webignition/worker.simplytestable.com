<?php

namespace App\Tests\Unit\Services\TaskTypePerformer;

use App\Model\Task\Type;
use App\Model\TaskPerformerCollection;
use App\Services\TaskTypePerformer\Factory;
use App\Services\TaskTypePerformer\TaskPerformerInterface;

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getPerformersDataProvider
     *
     * @param array $performers
     * @param string $taskType
     * @param array $expectedPerformers
     */
    public function testGetPerformers(array $performers, string $taskType, array $expectedPerformers)
    {
        $factory = new Factory($performers);

        $taskPerformerCollection = $factory->getPerformers($taskType);
        $this->assertInstanceOf(TaskPerformerCollection::class, $taskPerformerCollection);

        $expectedPerformerHashes = [];
        foreach ($expectedPerformers as $performerId) {
            $expectedPerformerHashes[] = spl_object_hash($performers[$performerId]);
        }

        $performerHashes = [];
        foreach ($taskPerformerCollection as $performerIndex => $taskPerformer) {
            $performerHashes[] = spl_object_hash($taskPerformer);
        }

        $this->assertEquals($expectedPerformerHashes, $performerHashes);
    }

    public function getPerformersDataProvider(): array
    {
        return [
            'no performers' => [
                'performers' => [],
                'taskType' => Type::TYPE_HTML_VALIDATION,
                'expectedPerformers' => [],
            ],
            'single performer, no match' => [
                'performers' => [
                    'A' => $this->createTaskPerformer(Type::TYPE_HTML_VALIDATION),
                ],
                'taskType' => Type::TYPE_CSS_VALIDATION,
                'expectedPerformers' => [],
            ],
            'multiple performers, none match' => [
                'performers' => [
                    'A' => $this->createTaskPerformer(Type::TYPE_HTML_VALIDATION),
                    'B' => $this->createTaskPerformer(Type::TYPE_CSS_VALIDATION),
                    'C' => $this->createTaskPerformer(Type::TYPE_LINK_INTEGRITY),
                ],
                'taskType' => Type::TYPE_URL_DISCOVERY,
                'expectedPerformers' => [],
            ],
            'single performer, is match' => [
                'performers' => [
                    'A' => $this->createTaskPerformer(Type::TYPE_HTML_VALIDATION),
                ],
                'taskType' => Type::TYPE_HTML_VALIDATION,
                'expectedPerformers' => ['A'],
            ],
            'multiple performers, some match' => [
                'performers' => [
                    'A' => $this->createTaskPerformer(Type::TYPE_HTML_VALIDATION),
                    'B' => $this->createTaskPerformer(Type::TYPE_CSS_VALIDATION),
                    'C' => $this->createTaskPerformer(Type::TYPE_HTML_VALIDATION),
                    'D' => $this->createTaskPerformer(Type::TYPE_LINK_INTEGRITY),
                    'E' => $this->createTaskPerformer(Type::TYPE_LINK_INTEGRITY_SINGLE_URL),
                ],
                'taskType' => Type::TYPE_HTML_VALIDATION,
                'expectedPerformers' => ['A', 'C'],
            ],
            'multiple performers, all match' => [
                'performers' => [
                    'A' => $this->createTaskPerformer(Type::TYPE_HTML_VALIDATION),
                    'B' => $this->createTaskPerformer(Type::TYPE_HTML_VALIDATION),
                    'C' => $this->createTaskPerformer(Type::TYPE_HTML_VALIDATION),
                ],
                'taskType' => Type::TYPE_HTML_VALIDATION,
                'expectedPerformers' => ['A', 'B', 'C'],
            ],
        ];
    }

    private function createTaskPerformer(string $handlesTaskType): TaskPerformerInterface
    {
        $taskTypes = [
            Type::TYPE_HTML_VALIDATION,
            Type::TYPE_CSS_VALIDATION,
            Type::TYPE_LINK_INTEGRITY,
            Type::TYPE_URL_DISCOVERY,
        ];

        $performer = \Mockery::mock(TaskPerformerInterface::class);
        $performer
            ->shouldReceive('getPriority')
            ->andReturn(1);

        foreach ($taskTypes as $taskType) {
            $handlesReturnValue = $handlesTaskType === $taskType;

            $performer
                ->shouldReceive('handles')
                ->with($taskType)
                ->andReturn($handlesReturnValue);
        }

        return $performer;
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
