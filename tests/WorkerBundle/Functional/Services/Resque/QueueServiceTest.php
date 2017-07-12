<?php

namespace Tests\WorkerBundle\Functional\Guzzle;

use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class QueueServiceTest extends BaseSimplyTestableTestCase
{
    const QUEUE_TASK_PERFORM = 'task-perform';
    const QUEUE_TASK_REPORT_COMPLETION = 'task-report-completion';
    const QUEUE_TASKS_REQUEST = 'tasks-request';

    /**
     * @dataProvider testIsEmptyDataProvider
     *
     * @param string $queue
     * @param array $jobArgs
     */
    public function testIsEmpty($queue, $jobArgs)
    {
        $this->clearRedis();
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $this->assertTrue($resqueQueueService->isEmpty($queue));

        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $resqueQueueService->enqueue(
            $resqueJobFactory->create($queue, $jobArgs)
        );
        $this->assertFalse($resqueQueueService->isEmpty($queue));
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
     */
    public function testContains($queue, $args, $jobDataCollection, $expectedContains)
    {
        $this->clearRedis();
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        foreach ($jobDataCollection as $jobData) {
            $resqueQueueService->enqueue(
                $resqueJobFactory->create($jobData['queue'], $jobData['args'])
            );
        }

        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $this->assertEquals(
            $expectedContains,
            $resqueQueueService->contains($queue, $args)
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
     */
    public function testGetQueueLength($queue, $jobDataCollection, $expectedQueueLength)
    {
        $this->clearRedis();
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        foreach ($jobDataCollection as $jobData) {
            $resqueQueueService->enqueue(
                $resqueJobFactory->create($jobData['queue'], $jobData['args'])
            );
        }

        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $this->assertEquals(
            $expectedQueueLength,
            $resqueQueueService->getQueueLength($queue)
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
