<?php

namespace Tests\WorkerBundle\Functional\Command\Maintenance;

use SimplyTestable\WorkerBundle\Command\Maintenance\RequeueInProgressTasksCommand;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\StateService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class RequeueInProgressTasksCommandTest extends AbstractBaseTestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param array $taskValuesCollection
     * @param array $commandArguments
     * @param int $expectedInitialQueuedTaskCount
     * @param int $expectedInitialInProgressTaskCount
     * @param int $expectedQueuedTaskCount
     * @throws \Exception
     */
    public function testRun(
        $taskValuesCollection,
        $commandArguments,
        $expectedInitialQueuedTaskCount,
        $expectedInitialInProgressTaskCount,
        $expectedQueuedTaskCount
    ) {
        $this->clearRedis();

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskRepository = $entityManager->getRepository(Task::class);
        $stateService = $this->container->get(StateService::class);

        $testTaskFactory = new TestTaskFactory($this->container);

        /* @var Task[] $tasks */
        $tasks = [];

        $queuedState = $stateService->fetch(Task::STATE_QUEUED);
        $inProgressState = $stateService->fetch(Task::STATE_IN_PROGRESS);

        foreach ($taskValuesCollection as $taskValues) {
            $tasks[] = $testTaskFactory->create($taskValues);
        }

        $this->assertCount(
            $expectedInitialQueuedTaskCount,
            $taskRepository->getIdsByState($queuedState)
        );

        $this->assertCount(
            $expectedInitialInProgressTaskCount,
            $taskRepository->getIdsByState($inProgressState)
        );

        $command = $this->container->get(RequeueInProgressTasksCommand::class);

        $returnCode = $command->run(
            new ArrayInput($commandArguments),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);

        $this->assertCount(
            $expectedQueuedTaskCount,
            $taskRepository->getIdsByState($queuedState)
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
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => Task::STATE_QUEUED,
                    ]),
                ],
                'commandArguments' => [],
                'expectedInitialQueuedTaskCount' => 1,
                'expectedInitialInProgressTaskCount' => 0,
                'expectedQueuedTaskCount' => 1,
            ],
            'one in-progress task not of suitable default age' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '10 minute',
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => Task::STATE_QUEUED,
                    ]),
                ],
                'commandArguments' => [],
                'expectedInitialQueuedTaskCount' => 1,
                'expectedInitialInProgressTaskCount' => 1,
                'expectedQueuedTaskCount' => 1,
            ],
            'one in-progress task of suitable default age' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '1 hour',
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => Task::STATE_QUEUED,
                    ]),
                ],
                'commandArguments' => [],
                'expectedInitialQueuedTaskCount' => 1,
                'expectedInitialInProgressTaskCount' => 1,
                'expectedQueuedTaskCount' => 2,
            ],
            'one in-progress task of suitable default age, dry run' => [
                'taskValuesCollection' => [
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '1 hour',
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => Task::STATE_QUEUED,
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
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '12 hour',
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => Task::STATE_QUEUED,
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
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '12 hour',
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => Task::STATE_QUEUED,
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
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/1/',
                        'state' => Task::STATE_IN_PROGRESS,
                        'age' => '12 hour',
                    ]),
                    TestTaskFactory::createTaskValuesFromDefaults([
                        'url' => 'http://example.com/2/',
                        'state' => Task::STATE_QUEUED,
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
