<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Guzzle;

use SimplyTestable\WorkerBundle\Resque\Job\TaskPerformJob;
use SimplyTestable\WorkerBundle\Resque\Job\TaskReportCompletionJob;
use SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;

class JobFactoryTest extends BaseSimplyTestableTestCase
{
    const RESQUE_JOB_FACTORY_SERVICE_ID = 'simplytestable.services.resque.jobfactory';

    public function testCreateWithInvalidQueue()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Queue "foo" is not valid',
            JobFactory::EXCEPTION_CODE_INVALID_QUEUE
        );

        $jobFactory = $this->container->get(self::RESQUE_JOB_FACTORY_SERVICE_ID);
        $jobFactory->create('foo');
    }

    /**
     * @dataProvider createWithMissingRequiredArgsDataProvider
     *
     * @param string $queue
     * @param array $args
     * @param string $expectedExceptionMessage
     */
    public function testCreateWithMissingRequiredArgs($queue, $args, $expectedExceptionMessage)
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            $expectedExceptionMessage,
            JobFactory::EXCEPTION_CODE_MISSING_REQUIRED_ARG
        );

        $jobFactory = $this->container->get('simplytestable.services.resque.jobfactory');
        $jobFactory->create($queue, $args);
    }

    /**
     * @return array
     */
    public function createWithMissingRequiredArgsDataProvider()
    {
        return [
            'task-perform' => [
                'queue' => 'task-perform',
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedExceptionMessage' => 'Required argument "id" is missing',
            ],
            'task-report-completion' => [
                'queue' => 'task-report-completion',
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedExceptionMessage' => 'Required argument "id" is missing',
            ],
        ];
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $queue
     * @param array $args
     */
    public function testCreate($queue, $args, $expectedJobClass, $expectedQueue, $expectedArgs)
    {
        $jobFactory = $this->container->get('simplytestable.services.resque.jobfactory');
        $job = $jobFactory->create($queue, $args);

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
                    'serviceIds' => [
                        'logger',
                        'simplytestable.services.taskservice',
                        'simplytestable.services.workerservice',
                        'simplytestable.services.resque.queueservice',
                        'simplytestable.services.resque.jobfactory',
                    ],
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
                    'serviceIds' => [
                        'logger',
                        'simplytestable.services.taskservice',
                        'simplytestable.services.workerservice',
                        'simplytestable.services.resque.queueservice',
                        'doctrine.orm.entity_manager',
                    ],
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
                    'serviceIds' => [
                        'simplytestable.services.tasksservice',
                        'simplytestable.services.workerservice',
                        'simplytestable.services.resque.queueservice',
                        'simplytestable.services.resque.jobfactory',
                    ],
                ],
            ],
        ];
    }
}

