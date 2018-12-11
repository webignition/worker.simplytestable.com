<?php

namespace App\Tests\Functional\Services;

use App\Event\TaskEvent;
use App\Model\Task\TypeInterface;
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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskPerformer = self::$container->get(TaskPerformer::class);
        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
    }

    /**
     * @dataProvider performDataProvider
     *
     * @param array $taskValues
     * @param array $httpFixtures
     * @param string $expectedFinishedStateName
     */
    public function testPerform($taskValues, $httpFixtures, $expectedFinishedStateName)
    {
        $this->httpMockHandler->appendFixtures($httpFixtures);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create($taskValues);

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
        $notFoundResponse = new Response(404);

        return [
            'default' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body></body>'
                    ),
                ],
                'expectedFinishedStateName' => Task::STATE_COMPLETED,
            ],
            'skipped' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'application/pdf']),
                ],
                'expectedFinishedStateName' => Task::STATE_SKIPPED,
            ],
            'failed, no retry available' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                ],
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
