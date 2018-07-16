<?php

namespace Tests\AppBundle\Functional\Services\Resque;

use App\Resque\Job\Job;
use App\Resque\Job\TaskPerformJob;
use App\Resque\Job\TaskReportCompletionJob;
use App\Resque\Job\TasksRequestJob;
use App\Services\Resque\QueueService;
use Tests\AppBundle\Functional\AbstractBaseTestCase;

class QueueServiceTest extends AbstractBaseTestCase
{
    const QUEUE_TASK_PERFORM = 'task-perform';
    const QUEUE_TASK_REPORT_COMPLETION = 'task-report-completion';
    const QUEUE_TASKS_REQUEST = 'tasks-request';

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

        $this->queueService = self::$container->get(QueueService::class);
    }

    /**
     * @dataProvider isEmptyDataProvider
     *
     * @param Job $job
     */
    public function testIsEmpty(Job $job)
    {
        $this->clearRedis();

        $this->assertTrue($this->queueService->isEmpty($job->queue));

        $this->queueService->enqueue($job);
        $this->assertFalse($this->queueService->isEmpty($job->queue));
    }

    /**
     * @return array
     */
    public function isEmptyDataProvider()
    {
        return [
            'task-perform' => [
                'job' => new TaskPerformJob(['id' => 1]),
            ],
            'task-report-completion' => [
                'job' => new TaskReportCompletionJob(['id' => 1]),
            ],
            'tasks-request' => [
                'job' => new TasksRequestJob(),
            ],
        ];
    }

    /**
     * @dataProvider containsDataProvider
     *
     * @param string $queue
     * @param array $args
     * @param Job[] $jobCollection
     * @param bool $expectedContains
     *
     * @throws \Exception
     */
    public function testContains($queue, array $args, array $jobCollection, $expectedContains)
    {
        $this->clearRedis();

        foreach ($jobCollection as $job) {
            $this->queueService->enqueue($job);
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
                'jobCollection' => [
                    new TaskPerformJob(['id' => 1]),
                ],
                'expectedContains' => false,
            ],
            'non-empty queue, task perform, does contain' => [
                'queue' => self::QUEUE_TASK_PERFORM,
                'args' => [
                    'id' => 1,
                ],
                'jobCollection' => [
                    new TaskPerformJob(['id' => 1]),
                ],
                'expectedContains' => true,
            ],
            'non-empty queue, task report completion, not contains' => [
                'queue' => self::QUEUE_TASK_REPORT_COMPLETION,
                'args' => [
                    'id' => 2,
                ],
                'jobCollection' => [
                    new TaskReportCompletionJob(['id' => 1]),
                ],
                'expectedContains' => false,
            ],
            'non-empty queue, task report completion, does contain' => [
                'queue' => self::QUEUE_TASK_REPORT_COMPLETION,
                'args' => [
                    'id' => 1,
                ],
                'jobCollection' => [
                    new TaskReportCompletionJob(['id' => 1]),
                ],
                'expectedContains' => true,
            ],
            'non-empty queue, tasks request, contains' => [
                'queue' => self::QUEUE_TASKS_REQUEST,
                'args' => [],
                'jobCollection' => [
                    new TasksRequestJob(),
                ],
                'expectedContains' => true,
            ],
        ];
    }

    /**
     * @dataProvider getQueueLengthDataProvider
     *
     * @param string $queue
     * @param Job[] $jobCollection
     * @param int $expectedQueueLength
     *
     * @throws \Exception
     */
    public function testGetQueueLength($queue, array $jobCollection, $expectedQueueLength)
    {
        $this->clearRedis();

        foreach ($jobCollection as $job) {
            $this->queueService->enqueue($job);
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
                'jobCollection' => [
                    new TaskPerformJob(['id' => 1]),
                ],
                'expectedQueueLength' => 1,
            ],
            'three task-perform jobs' => [
                'queue' => self::QUEUE_TASK_PERFORM,
                'jobCollection' => [
                    new TaskPerformJob(['id' => 1]),
                    new TaskPerformJob(['id' => 2]),
                    new TaskPerformJob(['id' => 3]),
                ],
                'expectedQueueLength' => 3,
            ],
        ];
    }
}
