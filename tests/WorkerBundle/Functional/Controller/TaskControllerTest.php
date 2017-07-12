<?php

namespace Tests\WorkerBundle\Functional\Controller;

use SimplyTestable\WorkerBundle\Controller\TaskController;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestFactory;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Tests\WorkerBundle\Factory\TaskFactory;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class TaskControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider createCollectionActionDataProvider
     *
     * @param array $postData
     * @param array $expectedResponseTaskCollection
     */
    public function testCreateCollectionAction($postData, $expectedResponseTaskCollection)
    {
        $this->removeAllTasks();

        $request = new Request();
        $request->request = $postData;
        $this->container->get('request_stack')->push($request);

        $response = $this->createTaskController()->createCollectionAction();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $decodedResponseContent = json_decode($response->getContent(), true);

        $this->assertCount(count($expectedResponseTaskCollection), $decodedResponseContent);

        foreach ($decodedResponseContent as $taskIndex => $responseTask) {
            $expectedResponseTask = $expectedResponseTaskCollection[$taskIndex];
            $this->assertInternalType('int', $responseTask['id']);
            $this->assertEquals($expectedResponseTask['type'], $responseTask['type']);
            $this->assertEquals($expectedResponseTask['url'], $responseTask['url']);
        }
    }

    /**
     * @return array
     */
    public function createCollectionActionDataProvider()
    {
        return [
            'no tasks data' => [
                'postData' => new ParameterBag([]),
                'expectedResponseTaskCollection' => []
            ],
            'empty tasks data' => [
                'postData' => new ParameterBag([
                    'tasks' => [],
                ]),
                'expectedResponseTaskCollection' => [],
            ],
            'single invalid task' => [
                'postData' => new ParameterBag([
                    'tasks' => [
                        [
                            CreateRequestFactory::PARAMETER_TYPE => 'foo',
                            CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
                        ],
                    ],
                ]),
                'expectedResponseTaskCollection' => [],
            ],
            'valid tasks' => [
                'postData' => new ParameterBag([
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
                ]),
                'expectedResponseTaskCollection' => [
                    [
                        'type' => 'HTML validation',
                        'url' => 'http://example.com/',
                    ],
                    [
                        'type' => 'CSS validation',
                        'url' => 'http://example.com/',
                    ],
                ],
            ],
        ];
    }

    public function testCancelAction()
    {
        $this->removeAllTasks();
        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults());
        $this->assertEquals('task-queued', $task->getState());

        $request = new Request();
        $request->request = new ParameterBag([
            CancelRequestFactory::PARAMETER_ID => $task->getId(),
        ]);
        $this->container->get('request_stack')->push($request);

        $response = $this->createTaskController()->cancelAction();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('task-cancelled', $task->getState());
    }

    public function testCancelCollectionAction()
    {
        $this->removeAllTasks();

        $taskIds = [];
        $tasks = [];
        $tasks[] = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'type' => 'html validation',
        ]));
        $tasks[] = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'type' => 'css validation',
        ]));

        foreach ($tasks as $task) {
            $taskIds[] = $task->getId();
            $this->assertEquals('task-queued', $task->getState());
        }

        $request = new Request();
        $request->request = new ParameterBag([
            CancelRequestCollectionFactory::PARAMETER_IDS => $taskIds,
        ]);
        $this->container->get('request_stack')->push($request);

        $response = $this->createTaskController()->cancelCollectionAction();

        $this->assertEquals(200, $response->getStatusCode());

        foreach ($tasks as $task) {
            $this->assertEquals('task-cancelled', $task->getState());
        }
    }

    /**
     * @return TaskController
     */
    private function createTaskController()
    {
        $controller = new TaskController();
        $controller->setContainer($this->container);

        return $controller;
    }
}
