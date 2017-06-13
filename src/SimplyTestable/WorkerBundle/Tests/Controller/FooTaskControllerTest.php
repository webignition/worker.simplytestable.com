<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller;

use SimplyTestable\WorkerBundle\Controller\TaskController;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestFactory;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FooTaskControllerTest extends BaseControllerJsonTestCase
{
    public function testCreateActionInMaintenanceReadOnlyMode()
    {
        $this->getWorkerService()->setReadOnly();
        $response = $this->createTaskController()->createAction();

        $this->assertEquals(503, $response->getStatusCode());
    }

    /**
     * @dataProvider createActionInvalidRequestDataProvider
     *
     * @param ParameterBag $postData
     */
    public function testCreateActionInvalidRequest(ParameterBag $postData)
    {
        $request = new Request();
        $request->request = $postData;
        $this->addRequestToContainer($request);

        $this->setExpectedException(
            BadRequestHttpException::class
        );

        $this->createTaskController()->createAction();
    }

    /**
     * @return array
     */
    public function createActionInvalidRequestDataProvider()
    {
        return [
            'no request parameters' => [
                'postData' => new ParameterBag([]),
            ],
            'type missing' => [
                'postData' => new ParameterBag([
                    CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
                ]),
            ],
            'url missing' => [
                'postData' => new ParameterBag([
                    CreateRequestFactory::PARAMETER_TYPE => 'html validation',
                ]),
            ],
            'invalid type' => [
                'postData' => new ParameterBag([
                    CreateRequestFactory::PARAMETER_TYPE => 'foo',
                    CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
                ]),
            ],
        ];
    }

    /**
     * @dataProvider createActionDataProvider
     *
     * @param array $postData
     * @param array $expectedResponseTaskData
     */
    public function testCreateAction($postData, $expectedResponseTaskData)
    {
        $this->removeAllTasks();

        $request = new Request();
        $request->request = $postData;
        $this->addRequestToContainer($request);

        $response = $this->createTaskController()->createAction();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $decodedResponseContent = json_decode($response->getContent(), true);

        foreach (array_keys($expectedResponseTaskData) as $key) {
            $this->assertTrue(isset($decodedResponseContent[$key]));
            $this->assertEquals($expectedResponseTaskData[$key], $decodedResponseContent[$key]);
        }

        $this->assertTrue(
            $this->getResqueQueueService()->contains(
                'task-perform',
                [
                    'id' => $decodedResponseContent['id']
                ]
            )
        );
    }

    /**
     * @return array
     */
    public function createActionDataProvider()
    {
        return [
            'html validation' => [
                'postData' => new ParameterBag([
                    CreateRequestFactory::PARAMETER_TYPE => 'html validation',
                    CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
                ]),
                'expectedResponseTaskData' => [
                    'url' => 'http://example.com/',
                    'state' => 'queued',
                    'type' => 'HTML validation',
                    'parameters' => '',
                ],
            ],
            'html validation with parameters' => [
                'postData' => new ParameterBag([
                    CreateRequestFactory::PARAMETER_TYPE => 'html validation',
                    CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
                    CreateRequestFactory::PARAMETER_PARAMETERS => 'foo',
                ]),
                'expectedResponseTaskData' => [
                    'url' => 'http://example.com/',
                    'state' => 'queued',
                    'type' => 'HTML validation',
                    'parameters' => 'foo',
                ],
            ],
            'css validation' => [
                'postData' => new ParameterBag([
                    CreateRequestFactory::PARAMETER_TYPE => 'css validation',
                    CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
                ]),
                'expectedResponseTaskData' => [
                    'url' => 'http://example.com/',
                    'state' => 'queued',
                    'type' => 'CSS validation',
                    'parameters' => '',
                ],
            ],
            'js static analysis' => [
                'postData' => new ParameterBag([
                    CreateRequestFactory::PARAMETER_TYPE => 'js static analysis',
                    CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
                ]),
                'expectedResponseTaskData' => [
                    'url' => 'http://example.com/',
                    'state' => 'queued',
                    'type' => 'JS static analysis',
                    'parameters' => '',
                ],
            ],
            'link integrity' => [
                'postData' => new ParameterBag([
                    CreateRequestFactory::PARAMETER_TYPE => 'link integrity',
                    CreateRequestFactory::PARAMETER_URL => 'http://example.com/',
                ]),
                'expectedResponseTaskData' => [
                    'url' => 'http://example.com/',
                    'state' => 'queued',
                    'type' => 'Link integrity',
                    'parameters' => '',
                ],
            ],
        ];
    }

    public function testCreateCollectionActionInMaintenanceReadOnlyMode()
    {
        $this->getWorkerService()->setReadOnly();
        $response = $this->createTaskController()->createCollectionAction();

        $this->assertEquals(503, $response->getStatusCode());
    }

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
        $this->addRequestToContainer($request);

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

    /**
     * @return TaskController
     */
    private function createTaskController()
    {
        $controller = new TaskController();
        $controller->setContainer($this->container);

        return $controller;
    }

    /**
     * @param Request $request
     */
    private function addRequestToContainer(Request $request)
    {
        $this->container->set('request', $request);
        $this->container->enterScope('request');
    }
}
