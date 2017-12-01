<?php

namespace Tests\WorkerBundle\Functional\Services\Resque;

use SimplyTestable\WorkerBundle\Resque\Job\TaskPerformJob;
use SimplyTestable\WorkerBundle\Resque\Job\TaskReportCompletionJob;
use SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob;
use webignition\ResqueJobFactory\ResqueJobFactory;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;

class JobFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var ResqueJobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = $this->container->get(ResqueJobFactory::class);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $queue
     * @param array $args
     * @param string $expectedJobClass
     * @param string $expectedQueue
     * @param array $expectedArgs
     */
    public function testCreateSuccess($queue, $args, $expectedJobClass, $expectedQueue, $expectedArgs)
    {
        $job = $this->jobFactory->create($queue, $args);

        $this->assertInstanceOf($expectedJobClass, $job);
        $this->assertEquals($job->queue, $expectedQueue);
        $this->assertEquals($job->args, $expectedArgs);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'task-perform' => [
                'queue' => 'task-perform',
                'args' => [
                    'id' => 1,
                ],
                'expectedJobClass' => TaskPerformJob::class,
                'expectedQueue' => 'task-perform',
                'expectedArgs' => [
                    'id' => 1,
                ],
            ],
            'task-report-completion' => [
                'queue' => 'task-report-completion',
                'args' => [
                    'id' => 1,
                ],
                'expectedJobClass' => TaskReportCompletionJob::class,
                'expectedQueue' => 'task-report-completion',
                'expectedArgs' => [
                    'id' => 1,
                ],
            ],
            'tasks-request' => [
                'queue' => 'tasks-request',
                'args' => [
                    'id' => 1,
                ],
                'expectedJobClass' => TasksRequestJob::class,
                'expectedQueue' => 'tasks-request',
                'expectedArgs' => [
                    'id' => 1,
                ],
            ],
        ];
    }

    /**
     * @dataProvider getJobClassNameDataProvider
     *
     * @param string $queue
     * @param string $expectedJobClassName
     */
    public function testGetJobClassName($queue, $expectedJobClassName)
    {
        $jobClassName = $this->jobFactory->getJobClassName($queue);

        $this->assertEquals($expectedJobClassName, $jobClassName);
    }

    /**
     * @return array
     */
    public function getJobClassNameDataProvider()
    {
        return [
            'task-perform' => [
                'queue' => 'task-perform',
                'expectedJobClassName' => 'SimplyTestable\WorkerBundle\Resque\Job\TaskPerformJob',
            ],
            'task-report-completion' => [
                'queue' => 'task-report-completion',
                'expectedJobClassName' => 'SimplyTestable\WorkerBundle\Resque\Job\TaskReportCompletionJob',
            ],
            'tasks-request' => [
                'queue' => 'tasks-request',
                'expectedJobClassName' => 'SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob',
            ],
        ];
    }
}
