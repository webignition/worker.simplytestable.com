<?php

namespace App\Tests\Functional\Command\Maintenance;

use App\Command\Maintenance\RequeueInProgressTasksCommand;
use App\Entity\Task\Task;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Factory\TestTaskFactory;
use App\Tests\Functional\AbstractBaseTestCase;
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

        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskRepository = $entityManager->getRepository(Task::class);

        $testTaskFactory = new TestTaskFactory(self::$container);

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($taskValuesCollection as $taskValues) {
            $tasks[] = $testTaskFactory->create($taskValues);
        }

        $this->assertCount(
            $expectedInitialQueuedTaskCount,
            $taskRepository->getIdsByState(Task::STATE_QUEUED)
        );

        $this->assertCount(
            $expectedInitialInProgressTaskCount,
            $taskRepository->getIdsByState(Task::STATE_IN_PROGRESS)
        );

        $command = self::$container->get(RequeueInProgressTasksCommand::class);

        $returnCode = $command->run(
            new ArrayInput($commandArguments),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);

        $this->assertCount(
            $expectedQueuedTaskCount,
            $taskRepository->getIdsByState(Task::STATE_QUEUED)
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
