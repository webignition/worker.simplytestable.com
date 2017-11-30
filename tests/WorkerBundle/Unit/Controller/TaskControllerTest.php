<?php

namespace Tests\WorkerBundle\Unit\Controller;

use SimplyTestable\WorkerBundle\Controller\TaskController;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestFactory;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TaskFactory;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\WorkerBundle\Factory\MockFactory;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class TaskControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateCollectionActionInMaintenanceReadOnlyMode()
    {
        $taskController = $this->createTaskController([
            WorkerService::class => MockFactory::createWorkerService([
                'isMaintenanceReadOnly' => [
                    'return' => true,
                ],
            ]),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $taskController->createCollectionAction(
            MockFactory::createCreateRequestCollectionFactory(),
            MockFactory::createTaskFactory(),
            MockFactory::createResqueQueueService(),
            MockFactory::createResqueJobFactory()
        );
    }

    /**
     * @param array $services
     *
     * @return TaskController
     */
    private function createTaskController($services = [])
    {
        if (!isset($services[WorkerService::class])) {
            $services[WorkerService::class] = MockFactory::createWorkerService();
        }

        if (!isset($services[TaskService::class])) {
            $services[TaskService::class] = MockFactory::createTaskService();
        }

        return new TaskController(
            $services[WorkerService::class],
            $services[TaskService::class]
        );
    }

//
//    /**
//     * @dataProvider createCollectionActionDataProvider
//     *
//     * @param array $postData
//     * @param array $expectedResponseTaskCollection
//     */
//    public function testCreateCollectionAction($postData, $expectedResponseTaskCollection)
//    {
//        $this->removeAllTasks();
//
//        $request = new Request();
//        $request->request = $postData;
//        $this->container->get('request_stack')->push($request);
//
//        $response = $this->taskController->createCollectionAction(
//            $this->container->get(CreateRequestCollectionFactory::class),
//            $this->container->get(TaskFactory::class),
//            $this->container->get(QueueService::class),
//            $this->container->get(JobFactory::class)
//        );
//
//        $this->assertEquals(200, $response->getStatusCode());
//        $this->assertEquals('application/json', $response->headers->get('content-type'));
//
//        $decodedResponseContent = json_decode($response->getContent(), true);
//
//        $this->assertCount(count($expectedResponseTaskCollection), $decodedResponseContent);
//
//        foreach ($decodedResponseContent as $taskIndex => $responseTask) {
//            $expectedResponseTask = $expectedResponseTaskCollection[$taskIndex];
//            $this->assertInternalType('int', $responseTask['id']);
//            $this->assertEquals($expectedResponseTask['type'], $responseTask['type']);
//            $this->assertEquals($expectedResponseTask['url'], $responseTask['url']);
//        }
//    }
//
//    /**
//     * @return array
//     */
//    public function createCollectionActionDataProvider()
//    {
//        return [
//            'no tasks data' => [
//                'postData' => new ParameterBag([]),
//                'expectedResponseTaskCollection' => []
//            ],
//            'empty tasks data' => [
//                'postData' => new ParameterBag([
//                    'tasks' => [],
//                ]),
//                'expectedResponseTaskCollection' => [],
//            ],
//            'single invalid task' => [
//                'postData' => new ParameterBag([
//                    'tasks' => [
//                        [
//                            CreateRequestFactory::PARAMETER_TYPE => 'foo',
//                            CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
//                        ],
//                    ],
//                ]),
//                'expectedResponseTaskCollection' => [],
//            ],
//            'valid tasks' => [
//                'postData' => new ParameterBag([
//                    'tasks' => [
//                        [
//                            CreateRequestFactory::PARAMETER_TYPE => 'html validation',
//                            CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
//                        ],
//                        [
//                            CreateRequestFactory::PARAMETER_TYPE => 'css validation',
//                            CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
//                        ],
//                    ],
//                ]),
//                'expectedResponseTaskCollection' => [
//                    [
//                        'type' => 'HTML validation',
//                        'url' => 'http://example.com/',
//                    ],
//                    [
//                        'type' => 'CSS validation',
//                        'url' => 'http://example.com/',
//                    ],
//                ],
//            ],
//        ];
//    }
//
//    public function testCancelCollectionActionInMaintenanceReadOnlyMode()
//    {
//        $this->expectException(ServiceUnavailableHttpException::class);
//
//        $request = new Request();
//        $request->request = new ParameterBag();
//        $this->container->get('request_stack')->push($request);
//
//        $workerService = $this->container->get(WorkerService::class);
//        $workerService->setReadOnly();
//
//        $this->taskController->cancelAction(
//            $this->container->get(CancelRequestFactory::class)
//        );
//    }
//
//    public function testCancelActionWithInvalidRequest()
//    {
//        $this->expectException(BadRequestHttpException::class);
//
//        $request = new Request();
//        $request->request = new ParameterBag();
//        $this->container->get('request_stack')->push($request);
//
//        $this->taskController->cancelAction(
//            $this->container->get(CancelRequestFactory::class)
//        );
//    }
//
//    public function testCancelAction()
//    {
//        $this->removeAllTasks();
//        $task = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults());
//        $this->assertEquals('task-queued', $task->getState());
//
//        $request = new Request();
//        $request->request = new ParameterBag([
//            CancelRequestFactory::PARAMETER_ID => $task->getId(),
//        ]);
//        $this->container->get('request_stack')->push($request);
//
//        $response = $this->taskController->cancelAction(
//            $this->container->get(CancelRequestFactory::class)
//        );
//
//        $this->assertEquals(200, $response->getStatusCode());
//        $this->assertEquals('task-cancelled', $task->getState());
//    }
//
//    public function testCancelCollectionAction()
//    {
//        $this->removeAllTasks();
//
//        $taskIds = [];
//        $tasks = [];
//        $tasks[] = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([
//            'type' => 'html validation',
//        ]));
//        $tasks[] = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([
//            'type' => 'css validation',
//        ]));
//
//        foreach ($tasks as $task) {
//            $taskIds[] = $task->getId();
//            $this->assertEquals('task-queued', $task->getState());
//        }
//
//        $request = new Request();
//        $request->request = new ParameterBag([
//            CancelRequestCollectionFactory::PARAMETER_IDS => $taskIds,
//        ]);
//        $this->container->get('request_stack')->push($request);
//
//        $response = $this->taskController->cancelCollectionAction(
//            $this->container->get(CancelRequestCollectionFactory::class)
//        );
//
//        $this->assertEquals(200, $response->getStatusCode());
//
//        foreach ($tasks as $task) {
//            $this->assertEquals('task-cancelled', $task->getState());
//        }
//    }
}
