<?php

namespace Tests\WorkerBundle\Functional\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\Maintenance\RequeueInProgressTasksCommand;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Tests\WorkerBundle\Factory\TaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class RequeueInProgressTasksCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param array $taskValuesCollection
     * @param array $commandArguments
     * @param int $expectedInitialQueuedTaskCount
     * @param int $expectedInitialInProgressTaskCount
     * @param int $expectedQueuedTaskCount
     */
    public function testRun(
        $taskValuesCollection,
        $commandArguments,
        $expectedInitialQueuedTaskCount,
        $expectedInitialInProgressTaskCount,
        $expectedQueuedTaskCount
    ) {
        $this->removeAllTasks();
        $this->clearRedis();

        $taskService = $this->container->get(TaskService::class);

        /* @var Task[] $tasks */
        $tasks = [];

        $queuedState = $taskService->getQueuedState();
        $inProgressState = $taskService->getInProgressState();

        foreach ($taskValuesCollection as $taskValues) {
            $tasks[] = $this->getTaskFactory()->create($taskValues);
        }

        $this->assertCount(
            $expectedInitialQueuedTaskCount,
            $taskService->getEntityRepository()->getIdsByState($queuedState)
        );

        $this->assertCount(
            $expectedInitialInProgressTaskCount,
            $taskService->getEntityRepository()->getIdsByState($inProgressState)
        );

        $command = $this->container->get(RequeueInProgressTasksCommand::class);

        $returnCode = $command->run(
            new ArrayInput($commandArguments),
            new StringOutput()
        );

        $this->assertEquals(0, $returnCode);

        $this->assertCount(
            $expectedQueuedTaskCount,
            $taskService->getEntityRepository()->getIdsByState($queuedState)
        );
    }

    /**
     * @return array
     */
    public function runDataProvider()
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
