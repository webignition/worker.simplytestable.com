<?php

namespace Tests\WorkerBundle\Functional\Guzzle;

use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class QueueServiceTest extends BaseSimplyTestableTestCase
{
    const QUEUE_TASK_PERFORM = 'task-perform';
    const QUEUE_TASK_REPORT_COMPLETION = 'task-report-completion';
    const QUEUE_TASKS_REQUEST = 'tasks-request';

    /**
     * @var ResqueJobFactory
     */
    private $jobFactory;

    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = $this->container->get(ResqueJobFactory::class);
        $this->queueService = $this->container->get(QueueService::class);
    }

    /**
     * @dataProvider testIsEmptyDataProvider
     *
     * @param string $queue
     * @param array $jobArgs
     *
     * @throws \CredisException
     * @throws \Exception
     */
    public function testIsEmpty($queue, $jobArgs)
    {
        $this->clearRedis();

        $this->assertTrue($this->queueService->isEmpty($queue));

        $this->queueService->enqueue(
            $this->jobFactory->create($queue, $jobArgs)
        );
        $this->assertFalse($this->queueService->isEmpty($queue));
    }

    /**
     * @return array
     */
    public function testIsEmptyDataProvider()
    {
        return [
            [
                'queue' => self::QUEUE_TASK_PERFORM,
                'jobArgs' => [
                    'id' => 1,
                ],
            ],
            [
                'queue' => self::QUEUE_TASK_REPORT_COMPLETION,
                'jobArgs' => [
                    'id' => 1,
                ],
            ],
            [
                'queue' => self::QUEUE_TASKS_REQUEST,
                'jobArgs' => [],
            ],
        ];
    }

    /**
     * @dataProvider containsDataProvider
     *
     * @param string $queue
     * @param array $args
     * @param array $jobDataCollection
     * @param bool $expectedContains
     *
     * @throws \CredisException
     * @throws \Exception
     */
    public function testContains($queue, $args, $jobDataCollection, $expectedContains)
    {
        $this->clearRedis();

        foreach ($jobDataCollection as $jobData) {
            $this->queueService->enqueue(
                $this->jobFactory->create($jobData['queue'], $jobData['args'])
            );
        }

        $this->assertEquals(
            $expectedContains,
            $this->queueService->contains($queue, $args)
        );
    }

    /**
     * @return array
     */
    public function containsDataProvider()
    {
        return [
            'empty queue, task-perform' => [
                'queue' => self::QUEUE_TASK_PERFORM,
                'args' => [],
                'jobDataCollection' => [],
                'expectedContains' => false,
            ],
            'empty queue, task-report-completion' => [
                'queue' => self::QUEUE_TASK_REPORT_COMPLETION,
                'args' => [],
                'jobDataCollection' => [],
                'expectedContains' => false,
            ],
            'empty queue, tasks-request' => [
                'queue' => self::QUEUE_TASKS_REQUEST,
                'args' => [],
                'jobDataCollection' => [],
                'expectedContains' => false,
            ],
            'non-empty queue, task perform, not contains' => [
                'queue' => self::QUEUE_TASK_PERFORM,
                'args' => [
                    'id' => 2,
                ],
                'jobDataCollection' => [
                    [
                        'queue' => self::QUEUE_TASK_PERFORM,
                        'args' => [
                            'id' => 1,
                        ],
                    ],
                ],
                'expectedContains' => false,
            ],
            'non-empty queue, task perform, does contain' => [
                'queue' => self::QUEUE_TASK_PERFORM,
                'args' => [
                    'id' => 1,
                ],
                'jobDataCollection' => [
                    [
                        'queue' => self::QUEUE_TASK_PERFORM,
                        'args' => [
                            'id' => 1,
                        ],
                    ],
                ],
                'expectedContains' => true,
            ],
            'non-empty queue, task report completion, not contains' => [
                'queue' => self::QUEUE_TASK_REPORT_COMPLETION,
                'args' => [
                    'id' => 2,
                ],
                'jobDataCollection' => [
                    [
                        'queue' => self::QUEUE_TASK_REPORT_COMPLETION,
                        'args' => [
                            'id' => 1,
                        ],
                    ],
                ],
                'expectedContains' => false,
            ],
            'non-empty queue, task report completion, does contain' => [
                'queue' => self::QUEUE_TASK_REPORT_COMPLETION,
                'args' => [
                    'id' => 1,
                ],
                'jobDataCollection' => [
                    [
                        'queue' => self::QUEUE_TASK_REPORT_COMPLETION,
                        'args' => [
                            'id' => 1,
                        ],
                    ],
                ],
                'expectedContains' => true,
            ],
            'non-empty queue, tasks request, contains' => [
                'queue' => self::QUEUE_TASKS_REQUEST,
                'args' => [],
                'jobDataCollection' => [
                    [
                        'queue' => self::QUEUE_TASKS_REQUEST,
                        'args' => [],
                    ],
                ],
                'expectedContains' => true,
            ],
        ];
    }

    /**
     * @dataProvider getQueueLengthDataProvider
     *
     * @param string $queue
     * @param array $jobDataCollection
     * @param int $expectedQueueLength
     *
     * @throws \CredisException
     * @throws \Exception
     */
    public function testGetQueueLength($queue, $jobDataCollection, $expectedQueueLength)
    {
        $this->clearRedis();

        foreach ($jobDataCollection as $jobData) {
            $this->queueService->enqueue(
                $this->jobFactory->create($jobData['queue'], $jobData['args'])
            );
        }

        $this->assertEquals(
            $expectedQueueLength,
            $this->queueService->getQueueLength($queue)
        );
    }

    /**
     * @return array
     */
    public function getQueueLengthDataProvider()
    {
        return [
            'empty task-perform' => [
                'queue' => self::QUEUE_TASK_PERFORM,
                'jobDataCollection' => [],
                'expectedQueueLength' => 0,
            ],
            'empty task-report-completion' => [
                'queue' => self::QUEUE_TASK_REPORT_COMPLETION,
                'jobDataCollection' => [],
                'expectedQueueLength' => 0,
            ],
            'empty tasks-request' => [
                'queue' => self::QUEUE_TASKS_REQUEST,
                'jobDataCollection' => [],
                'expectedQueueLength' => 0,
            ],
            'one task-perform job' => [
                'queue' => self::QUEUE_TASK_PERFORM,
                'jobDataCollection' => [
                    [
                        'queue' => self::QUEUE_TASK_PERFORM,
                        'args' => [
                            'id' => 1,
                        ],
                    ],
                ],
                'expectedQueueLength' => 1,
            ],
            'three task-perform jobs' => [
                'queue' => self::QUEUE_TASK_PERFORM,
                'jobDataCollection' => [
                    [
                        'queue' => self::QUEUE_TASK_PERFORM,
                        'args' => [
                            'id' => 1,
                        ],
                    ],
                    [
                        'queue' => self::QUEUE_TASK_PERFORM,
                        'args' => [
                            'id' => 2,
                        ],
                    ],
                    [
                        'queue' => self::QUEUE_TASK_PERFORM,
                        'args' => [
                            'id' => 3,
                        ],
                    ],
                ],
                'expectedQueueLength' => 3,
            ],
        ];
    }
}
