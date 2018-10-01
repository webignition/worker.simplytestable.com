<?php

namespace App\Tests\Functional\Services;

use App\Model\Task\TypeInterface;
use App\Services\TaskPerformer;
use App\Tests\TestServices\TaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Entity\Task\Task;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use App\Tests\Services\HttpMockHandler;

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
     * @var TaskFactory
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
        $this->testTaskFactory = self::$container->get(TaskFactory::class);
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
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([]),
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
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'application/pdf']),
                ],
                'expectedFinishedStateName' => Task::STATE_SKIPPED,
            ],
            'failed, no retry available' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([]),
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
