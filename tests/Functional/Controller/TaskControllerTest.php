<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Task\Task;
use App\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use App\Services\Request\Factory\Task\CancelRequestFactory;
use App\Services\Request\Factory\Task\CreateRequestFactory;
use App\Tests\Factory\TestTaskFactory;

/**
 * @group Controller/TaskController
 */
class TaskControllerTest extends AbstractControllerTest
{
    /**
     * @dataProvider createCollectionActionDataProvider
     *
     * @param array $postData
     * @param array $expectedResponseTaskCollection
     */
    public function testCreateCollectionAction($postData, $expectedResponseTaskCollection)
    {
        $this->client->request(
            'POST',
            $this->router->generate('task_create_collection'),
            $postData
        );

        $response = $this->client->getResponse();

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
                'postData' => [],
                'expectedResponseTaskCollection' => []
            ],
            'empty tasks data' => [
                'postData' => [
                    'tasks' => [],
                ],
                'expectedResponseTaskCollection' => [],
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
                'expectedResponseTaskCollection' => [],
            ],
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
                'expectedResponseTaskCollection' => [
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
        $testTaskFactory = new TestTaskFactory(self::$container);

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
        $testTaskFactory = new TestTaskFactory(self::$container);

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
