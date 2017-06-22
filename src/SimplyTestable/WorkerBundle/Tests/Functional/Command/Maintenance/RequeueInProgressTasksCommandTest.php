<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\Maintenance\RequeueInProgressTasksCommand;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
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

        $command = $this->createRequeueInProgressTasksCommand();
        $returnCode = $command->run(
            new ArrayInput($commandArguments),
            new StringOutput()
        );

        $this->assertEquals(0, $returnCode);

        $this->assertCount(
            $expectedQueuedTaskCount,
            $this->getTaskService()->getEntityRepository()->getIdsByState($queuedState)
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

    /**
     * @return RequeueInProgressTasksCommand
     */
    private function createRequeueInProgressTasksCommand()
    {
        return new RequeueInProgressTasksCommand(
            $this->container->get('simplytestable.services.taskservice'),
            $this->container->get('simplytestable.services.resque.queueservice'),
            $this->container->get('simplytestable.services.resque.jobfactory')
        );
    }
}
