<?php

namespace App\Tests\Functional\Controller;

use App\Controller\TaskController;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Resque\Job\TaskPrepareJob;
use App\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use App\Services\Request\Factory\Task\CancelRequestFactory;
use App\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use App\Services\Request\Factory\Task\CreateRequestFactory;
use App\Services\Resque\QueueService;
use App\Services\TaskFactory;
use App\Tests\Services\TestTaskFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @group Controller/TaskController
 */
class TaskControllerTest extends AbstractControllerTest
{
    /**
     * @dataProvider createCollectionActionNoTasksCreatedDataProvider
     *
     * @param array $postData
     */
    public function testCreateCollectionActionNoTasksCreated(array $postData)
    {
        $taskController = self::$container->get(TaskController::class);

        $requestStack = self::$container->get(RequestStack::class);
        $request = new Request([], $postData);
        $request->setMethod(Request::METHOD_POST);

        $requestStack->push($request);

        $createRequestCollectionFactory = self::$container->get(CreateRequestCollectionFactory::class);
        $taskFactory = self::$container->get(TaskFactory::class);
        $resqueQueueService = self::$container->get(QueueService::class);

        $response = $taskController->createCollectionAction(
            $createRequestCollectionFactory,
            $taskFactory,
            \Mockery::mock(QueueService::class),
            \Mockery::mock(EventDispatcherInterface::class)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $decodedResponseContent = json_decode($response->getContent(), true);

        $this->assertEmpty($decodedResponseContent);
        $this->assertTrue($resqueQueueService->isEmpty('task-prepare'));
    }

    /**
     * @return array
     */
    public function createCollectionActionNoTasksCreatedDataProvider()
    {
        return [
            'no tasks data' => [
                'postData' => [],
            ],
            'empty tasks data' => [
                'postData' => [
                    'tasks' => [],
                ],
            ],
            'single invalid task' => [
                'postData' => [
                    'tasks' => [
                        [
                            CreateRequestFactory::PARAMETER_TYPE => 'foo',
                            CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider createCollectionActionTasksCreatedDataProvider
     *
     * @param array $postData
     * @param array $expectedTaskCollection
     */
    public function testCreateCollectionActionTasksCreated($postData, $expectedTaskCollection)
    {
        $request = new Request([], $postData);
        $request->setMethod(Request::METHOD_POST);
        self::$container->get(RequestStack::class)->push($request);

        $createRequestCollectionFactory = self::$container->get(CreateRequestCollectionFactory::class);
        $taskFactory = self::$container->get(TaskFactory::class);
        $taskController = self::$container->get(TaskController::class);

        $eventDispatcher = \Mockery::spy(EventDispatcherInterface::class);
        $resqueQueueService = \Mockery::spy(QueueService::class);

        $response = $taskController->createCollectionAction(
            $createRequestCollectionFactory,
            $taskFactory,
            $resqueQueueService,
            $eventDispatcher
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $decodedResponseContent = json_decode($response->getContent(), true);

        $this->assertCount(count($expectedTaskCollection), $decodedResponseContent);

        $taskIds = [];

        /* @var TaskPrepareJob[] $expectedTaskPrepareJobs */
        $expectedTaskPrepareJobs = [];

        foreach ($decodedResponseContent as $taskIndex => $responseTask) {
            $expectedResponseTask = $expectedTaskCollection[$taskIndex];

            $this->assertArrayHasKey('id', $responseTask);
            $taskId = $responseTask['id'];

            $this->assertInternalType('int', $taskId);
            $this->assertEquals($expectedResponseTask['type'], $responseTask['type']);
            $this->assertEquals($expectedResponseTask['url'], $responseTask['url']);

            $taskIds[] = $taskId;
            $expectedTaskPrepareJobs[] = new TaskPrepareJob(['id' => $taskId]);
        }

        $taskPrepareJobIndex = 0;

        $resqueQueueService
            ->shouldHaveReceived('enqueue')
            ->withArgs(function (TaskPrepareJob $taskPrepareJob) use (&$taskPrepareJobIndex, $expectedTaskPrepareJobs) {
                $expectedTaskPrepareJob = $expectedTaskPrepareJobs[$taskPrepareJobIndex];

                $this->assertEquals($expectedTaskPrepareJob->queue, $taskPrepareJob->queue);
                $this->assertEquals($expectedTaskPrepareJob->args, $taskPrepareJob->args);

                $taskPrepareJobIndex++;

                return true;
            });

        $taskIndex = 0;

        $eventDispatcher
            ->shouldHaveReceived('dispatch')
            ->withArgs(function (string $eventName, TaskEvent $taskEvent) use (&$taskIndex, $expectedTaskCollection) {
                $this->assertEquals(TaskEvent::TYPE_CREATED, $eventName);

                $task = $taskEvent->getTask();
                $expectedTaskData = $expectedTaskCollection[$taskIndex];

                $this->assertEquals($expectedTaskData['url'], $task->getUrl());
                $this->assertEquals($expectedTaskData['type'], $task->getType()->getName());

                $taskIndex++;

                return true;
            });
    }

    /**
     * @return array
     */
    public function createCollectionActionTasksCreatedDataProvider()
    {
        return [
            'valid tasks' => [
                'postData' => [
                    'tasks' => [
                        [
                            CreateRequestFactory::PARAMETER_TYPE => 'html validation',
                            CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
                        ],
                        [
                            CreateRequestFactory::PARAMETER_TYPE => 'css validation',
                            CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
                        ],
                    ],
                ],
                'expectedTaskCollection' => [
                    [
                        'type' => 'html validation',
                        'url' => 'http://example.com/',
                    ],
                    [
                        'type' => 'css validation',
                        'url' => 'http://example.com/',
                    ],
                ],
            ],
        ];
    }

    public function testCancelAction()
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
        $this->assertEquals(Task::STATE_QUEUED, $task->getState());

        $this->client->request(
            'POST',
            $this->router->generate('task_cancel'),
            [
                CancelRequestFactory::PARAMETER_ID => $task->getId(),
            ]
        );

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(Task::STATE_CANCELLED, $task->getState());
    }

    public function testCancelCollectionAction()
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $taskIds = [];
        $tasks = [];
        $tasks[] = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => 'html validation',
        ]));
        $tasks[] = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => 'css validation',
        ]));

        foreach ($tasks as $task) {
            $taskIds[] = $task->getId();
            $this->assertEquals(Task::STATE_QUEUED, $task->getState());
        }

        $this->client->request(
            'POST',
            $this->router->generate('task_cancel_collection'),
            [
                CancelRequestCollectionFactory::PARAMETER_IDS => $taskIds,
            ]
        );

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        foreach ($tasks as $task) {
            $this->assertEquals(Task::STATE_CANCELLED, $task->getState());
        }
    }
}
