<?php

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\SourceFactory;
use App\Services\TaskTypePerformer\WebPageTaskInvalidSourceExaminer;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestTaskFactory;

class WebPageTaskInvalidSourceExaminerTest extends AbstractBaseTestCase
{
    /**
     * @var WebPageTaskInvalidSourceExaminer
     */
    private $examiner;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->examiner = self::$container->get(WebPageTaskInvalidSourceExaminer::class);
    }

    /**
     * @dataProvider performNoChangesDataProvider
     *
     * @param callable $taskCreator
     */
    public function testPerformNoChanges(callable $taskCreator)
    {
        /* @var Task $task */
        $task = $taskCreator();

        $taskState = $task->getState();

        $this->examiner->perform($task);

        $this->assertEquals($taskState, $task->getState());
        $this->assertNull($task->getOutput());
    }

    public function performNoChangesDataProvider()
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
     * @dataProvider performSetsTaskAsSkippedDataProvider
     *
     * @param callable $taskCreator
     */
    public function testPerformSetsTaskAsSkipped(callable $taskCreator)
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $sourceFactory = self::$container->get(SourceFactory::class);
        $cachedResourceFactory = self::$container->get(CachedResourceFactory::class);
        $cachedResourceManager = self::$container->get(CachedResourceManager::class);

        /* @var Task $task */
        $task = $taskCreator($testTaskFactory, $sourceFactory, $cachedResourceFactory, $cachedResourceManager);

        $this->assertEquals(Task::STATE_QUEUED, $task->getState());
        $this->assertNull($task->getOutput());

        $this->examiner->perform($task);

        $this->assertEquals(Task::STATE_SKIPPED, $task->getState());

        $taskOutput = $task->getOutput();
        $this->assertInstanceOf(Output::class, $taskOutput);
        $this->assertEquals(0, $taskOutput->getErrorCount());
        $this->assertEquals(0, $taskOutput->getWarningCount());
        $this->assertEquals('', $taskOutput->getOutput());
    }

    public function performSetsTaskAsSkippedDataProvider()
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

    public function testHandles()
    {
        $this->assertTrue($this->examiner->handles(TypeInterface::TYPE_HTML_VALIDATION));
        $this->assertTrue($this->examiner->handles(TypeInterface::TYPE_CSS_VALIDATION));
        $this->assertTrue($this->examiner->handles(TypeInterface::TYPE_LINK_INTEGRITY));
        $this->assertTrue($this->examiner->handles(TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL));
        $this->assertTrue($this->examiner->handles(TypeInterface::TYPE_URL_DISCOVERY));
    }

    public function testGetPriority()
    {
        $this->assertEquals(
            self::$container->getParameter('web_page_task_invalid_source_examiner_priority'),
            $this->examiner->getPriority()
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
