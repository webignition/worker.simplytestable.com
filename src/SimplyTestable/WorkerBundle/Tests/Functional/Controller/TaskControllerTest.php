<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Controller;

use SimplyTestable\WorkerBundle\Controller\TaskController;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestFactory;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class TaskControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider createCollectionActionDataProvider
     *
     * @param array $postData
     * @param int $expectedResponseTaskCollectionCount
     */
    public function testCreateCollectionAction($postData, $expectedResponseTaskCollectionCount)
    {
        $this->removeAllTasks();

        $request = new Request();
        $request->request = $postData;
        $this->container->get('request_stack')->push($request);

        $response = $this->createTaskController()->createCollectionAction();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $decodedResponseContent = json_decode($response->getContent(), true);

        $this->assertCount($expectedResponseTaskCollectionCount, $decodedResponseContent);
    }

    /**
     * @return array
     */
    public function createCollectionActionDataProvider()
    {
        return [
            'no tasks data' => [
                'postData' => new ParameterBag([]),
                'expectedResponseTaskCollectionCount' => 0,
            ],
            'empty tasks data' => [
                'postData' => new ParameterBag([
                    'tasks' => [],
                ]),
                'expectedResponseTaskCollectionCount' => 0,
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
                'expectedResponseTaskCollectionCount' => 0,
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
                'expectedResponseTaskCollectionCount' => 2,
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
