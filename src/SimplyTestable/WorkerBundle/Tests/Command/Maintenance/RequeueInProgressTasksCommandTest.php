<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\Maintenance\RequeueInProgressTasksCommand;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

class RequeueInProgressTasksCommandTest extends ConsoleCommandBaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAdditionalCommands()
    {
        return array(
            new RequeueInProgressTasksCommand(),
        );
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $taskValuesCollection
     * @param int $expectedInitialQueuedTaskCount
     * @param int $expectedInitialInProgressTaskCount
     * @param int $expectedQueuedTaskCount
     */
    public function testExecute(
        $taskValuesCollection,
        $commandArguments,
        $expectedInitialQueuedTaskCount,
        $expectedInitialInProgressTaskCount,
        $expectedQueuedTaskCount
    ) {
        $this->removeAllTasks();

        /* @var Task[] $tasks */
        $tasks = [];

        $queuedState = $this->getTaskService()->getQueuedState();
        $inProgressState = $this->getTaskService()->getInProgressState();

        foreach ($taskValuesCollection as $taskValues) {
            $tasks[] = $this->getTaskFactory()->create($taskValues);
        }

        $this->assertCount(
            $expectedInitialQueuedTaskCount,
            $this->getTaskService()->getEntityRepository()->getIdsByState($queuedState)
        );

        $this->assertCount(
            $expectedInitialInProgressTaskCount,
            $this->getTaskService()->getEntityRepository()->getIdsByState($inProgressState)
        );

        $this->clearRedis();

        $this->assertEquals(
            0,
            $this->executeCommand('simplytestable:maintenance:requeue-in-progress-tasks', $commandArguments)
        );

        $this->assertCount(
            $expectedQueuedTaskCount,
            $this->getTaskService()->getEntityRepository()->getIdsByState($queuedState)
        );
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'no in-progress tasks' => [
                'taskValuesCollection' => [
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => TaskService::TASK_STARTING_STATE,
                    ]),
                ],
                'commandArguments' => [],
                'expectedInitialQueuedTaskCount' => 1,
                'expectedInitialInProgressTaskCount' => 0,
                'expectedQueuedTaskCount' => 1,
            ],
            'one in-progress task not of suitable default age' => [
                'taskValuesCollection' => [
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => TaskService::TASK_IN_PROGRESS_STATE,
                        'age' => '10 minute',
                    ]),
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => TaskService::TASK_STARTING_STATE,
                    ]),
                ],
                'commandArguments' => [],
                'expectedInitialQueuedTaskCount' => 1,
                'expectedInitialInProgressTaskCount' => 1,
                'expectedQueuedTaskCount' => 1,
            ],
            'one in-progress task of suitable default age' => [
                'taskValuesCollection' => [
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => TaskService::TASK_IN_PROGRESS_STATE,
                        'age' => '1 hour',
                    ]),
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => TaskService::TASK_STARTING_STATE,
                    ]),
                ],
                'commandArguments' => [],
                'expectedInitialQueuedTaskCount' => 1,
                'expectedInitialInProgressTaskCount' => 1,
                'expectedQueuedTaskCount' => 2,
            ],
            'one in-progress task of suitable default age, dry run' => [
                'taskValuesCollection' => [
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => TaskService::TASK_IN_PROGRESS_STATE,
                        'age' => '1 hour',
                    ]),
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => TaskService::TASK_STARTING_STATE,
                    ]),
                ],
                'commandArguments' => [
                    '--dry-run' => true,
                ],
                'expectedInitialQueuedTaskCount' => 1,
                'expectedInitialInProgressTaskCount' => 1,
                'expectedQueuedTaskCount' => 1,
            ],
            'one in-progress task of suitable non-default age' => [
                'taskValuesCollection' => [
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => TaskService::TASK_IN_PROGRESS_STATE,
                        'age' => '12 hour',
                    ]),
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => TaskService::TASK_STARTING_STATE,
                    ]),
                ],
                'commandArguments' => [
                    '--age-in-hours' => 11,
                ],
                'expectedInitialQueuedTaskCount' => 1,
                'expectedInitialInProgressTaskCount' => 1,
                'expectedQueuedTaskCount' => 2,
            ],
            'one in-progress task of suitable default age, invalid bool age-in-hours' => [
                'taskValuesCollection' => [
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => TaskService::TASK_IN_PROGRESS_STATE,
                        'age' => '12 hour',
                    ]),
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => TaskService::TASK_STARTING_STATE,
                    ]),
                ],
                'commandArguments' => [
                    '--age-in-hours' => true,
                ],
                'expectedInitialQueuedTaskCount' => 1,
                'expectedInitialInProgressTaskCount' => 1,
                'expectedQueuedTaskCount' => 2,
            ],
            'one in-progress task of suitable default age, invalid zero age-in-hours' => [
                'taskValuesCollection' => [
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => TaskService::TASK_IN_PROGRESS_STATE,
                        'age' => '12 hour',
                    ]),
                    TaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => TaskService::TASK_STARTING_STATE,
                    ]),
                ],
                'commandArguments' => [
                    '--age-in-hours' => 0,
                ],
                'expectedInitialQueuedTaskCount' => 1,
                'expectedInitialInProgressTaskCount' => 1,
                'expectedQueuedTaskCount' => 2,
            ],
        ];
    }
}
