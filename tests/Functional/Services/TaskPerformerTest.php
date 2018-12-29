<?php

namespace App\Tests\Functional\Services;

use App\Event\TaskEvent;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\SourceFactory;
use App\Services\TaskPerformer;
use App\Tests\Services\ObjectPropertySetter;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Entity\Task\Task;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use App\Tests\Services\HttpMockHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaskPerformerTest extends AbstractBaseTestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TypeInterface::TYPE_HTML_VALIDATION;
    const DEFAULT_TASK_STATE = Task::STATE_QUEUED;

    /**
     * @var TaskPerformer
     */
    private $taskPerformer;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * @var SourceFactory
     */
    private $sourceFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskPerformer = self::$container->get(TaskPerformer::class);
        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->sourceFactory = self::$container->get(SourceFactory::class);
    }

    /**
     * @dataProvider performDataProvider
     *
     * @param callable $taskCreator
     * @param callable $setUp
     * @param string $expectedFinishedStateName
     */
    public function testPerform(
        callable $taskCreator,
        callable $setUp,
        string $expectedFinishedStateName
    ) {
        /* @var Task $task */
        $task = $taskCreator($this->testTaskFactory, $this->sourceFactory);
        $setUp($this->httpMockHandler);

        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->withArgs(function (string $eventName, TaskEvent $taskEvent) use ($task) {
                $this->assertEquals(TaskEvent::TYPE_PERFORMED, $eventName);
                $this->assertSame($task, $taskEvent->getTask());

                return true;
            });

        ObjectPropertySetter::setProperty(
            $this->taskPerformer,
            TaskPerformer::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $this->taskPerformer->perform($task);

        $this->assertEquals($expectedFinishedStateName, $task->getState());
    }

    /**
     * @return array
     */
    public function performDataProvider()
    {
        return [
            'html validation success, no sources' => [
                'task' => function (TestTaskFactory $testTaskFactory): Task {
                    return $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults([])
                    );
                },
                'setUp' => function (HttpMockHandler $httpMockHandler) {
                    $httpMockHandler->appendFixtures([
                        new Response(200, ['content-type' => 'text/html']),
                        new Response(
                            200,
                            ['content-type' => 'text/html'],
                            '<!doctype html><html><head></head><body></body>'
                        ),
                    ]);
                    HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));
                },
                'expectedFinishedStateName' => Task::STATE_COMPLETED,
            ],
            'skipped, no sources' => [
                'task' => function (TestTaskFactory $testTaskFactory): Task {
                    return $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults([])
                    );
                },
                'setUp' => function (HttpMockHandler $httpMockHandler) {
                    $httpMockHandler->appendFixtures([
                        new Response(200, ['content-type' => 'application/pdf']),
                    ]);
                },
                'expectedFinishedStateName' => Task::STATE_SKIPPED,
            ],
            'skipped, has source' => [
                'task' => function (TestTaskFactory $testTaskFactory, SourceFactory $sourceFactory): Task {
                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults([])
                    );

                    $source = $sourceFactory->createInvalidSource(
                        $task->getUrl(),
                        Source::MESSAGE_INVALID_CONTENT_TYPE
                    );

                    $task->addSource($source);

                    return $task;
                },
                'setUp' => function () {
                },
                'expectedFinishedStateName' => Task::STATE_SKIPPED,
            ],
            'failed no retry available, no sources' => [
                'task' => function (TestTaskFactory $testTaskFactory): Task {
                    return $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults([])
                    );
                },
                'setUp' => function (HttpMockHandler $httpMockHandler) {
                    $notFoundResponse = new Response(404);

                    $httpMockHandler->appendFixtures([
                        $notFoundResponse,
                        $notFoundResponse,
                    ]);
                },
                'expectedFinishedStateName' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
            ],
            'failed no retry available, has source' => [
                'task' => function (TestTaskFactory $testTaskFactory, SourceFactory $sourceFactory): Task {
                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults([])
                    );

                    $source = $sourceFactory->createHttpFailedSource(
                        $task->getUrl(),
                        404
                    );

                    $task->addSource($source);

                    return $task;
                },
                'setUp' => function () {
                },
                'expectedFinishedStateName' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
