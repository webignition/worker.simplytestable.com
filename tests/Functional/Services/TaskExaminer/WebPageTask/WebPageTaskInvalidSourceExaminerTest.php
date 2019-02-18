<?php

namespace App\Tests\Functional\Services\TaskExaminer\WebPageTask;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\SourceFactory;
use App\Services\TaskExaminer\WebPageTask\InvalidSourceExaminer;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestTaskFactory;

class WebPageTaskInvalidSourceExaminerTest extends AbstractBaseTestCase
{
    /**
     * @var InvalidSourceExaminer
     */
    private $examiner;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->examiner = self::$container->get(InvalidSourceExaminer::class);
    }

    /**
     * @dataProvider examineNoChangesDataProvider
     *
     * @param callable $taskCreator
     */
    public function testExamineNoChanges(callable $taskCreator)
    {
        /* @var Task $task */
        $task = $taskCreator();

        $taskState = $task->getState();

        $this->examiner->examine($task);

        $this->assertEquals($taskState, $task->getState());
        $this->assertNull($task->getOutput());
    }

    public function examineNoChangesDataProvider()
    {
        return [
            'no sources' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );
                },
            ],
            'no primary source' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $task->addSource(
                        $sourceFactory->createHttpFailedSource('http://example.com/404', 404)
                    );

                    return $task;
                },
            ],
            'invalid primary source, not invalid content type' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $task->addSource(
                        $sourceFactory->createHttpFailedSource($task->getUrl(), 404)
                    );

                    return $task;
                },
            ],
            'non-empty cached resource' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $testTaskFactory->addPrimaryCachedResourceSourceToTask($task, 'non-empty content');

                    return $task;
                },
            ],
        ];
    }

    /**
     * @dataProvider examineSetsTaskAsSkippedDataProvider
     *
     * @param callable $taskCreator
     */
    public function testExamineSetsTaskAsSkipped(callable $taskCreator)
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $sourceFactory = self::$container->get(SourceFactory::class);
        $cachedResourceFactory = self::$container->get(CachedResourceFactory::class);
        $cachedResourceManager = self::$container->get(CachedResourceManager::class);

        /* @var Task $task */
        $task = $taskCreator($testTaskFactory, $sourceFactory, $cachedResourceFactory, $cachedResourceManager);

        $this->assertEquals(Task::STATE_QUEUED, $task->getState());
        $this->assertNull($task->getOutput());

        $this->examiner->examine($task);

        $this->assertEquals(Task::STATE_SKIPPED, $task->getState());

        $taskOutput = $task->getOutput();
        $this->assertInstanceOf(Output::class, $taskOutput);
        $this->assertEquals(0, $taskOutput->getErrorCount());
        $this->assertEquals(0, $taskOutput->getWarningCount());
        $this->assertEquals('', $taskOutput->getOutput());
    }

    public function examineSetsTaskAsSkippedDataProvider()
    {
        return [
            'invalid primary source, is invalid content type' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );

                    $source = $sourceFactory->createInvalidSource(
                        $task->getUrl(),
                        Source::MESSAGE_INVALID_CONTENT_TYPE
                    );

                    $task->addSource($source);

                    return $task;
                },
            ],
            'empty cached resource' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults()
                    );
                    $testTaskFactory->addPrimaryCachedResourceSourceToTask($task, '');

                    return $task;
                },
            ],
        ];
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
