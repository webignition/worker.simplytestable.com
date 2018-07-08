<?php

namespace Tests\WorkerBundle\Functional\Controller;

use SimplyTestable\WorkerBundle\Controller\TaskController;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TaskFactory;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Controller/TaskController
 */
class TaskControllerTest extends AbstractBaseTestCase
{
    /**
     * @var TaskController
     */
    private $taskController;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskController = $this->container->get(TaskController::class);

        $this->testTaskFactory = new TestTaskFactory($this->container);
    }

    /**
     * @dataProvider createCollectionActionDataProvider
     *
     * @param array $postData
     * @param array $expectedResponseTaskCollection
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function testCreateCollectionAction($postData, $expectedResponseTaskCollection)
    {
        $request = new Request();
        $request->request = $postData;
        $this->container->get('request_stack')->push($request);

        $response = $this->taskController->createCollectionAction(
            $this->container->get(CreateRequestCollectionFactory::class),
            $this->container->get(TaskFactory::class),
            $this->container->get(QueueService::class)
        );

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

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testCancelAction()
    {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
        $this->assertEquals('task-queued', $task->getState());

        $request = new Request();
        $request->request = new ParameterBag([
            CancelRequestFactory::PARAMETER_ID => $task->getId(),
        ]);
        $this->container->get('request_stack')->push($request);

        $response = $this->taskController->cancelAction(
            $this->container->get(CancelRequestFactory::class)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('task-cancelled', $task->getState());
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testCancelCollectionAction()
    {
        $taskIds = [];
        $tasks = [];
        $tasks[] = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => 'html validation',
        ]));
        $tasks[] = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
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

        $response = $this->taskController->cancelCollectionAction(
            $this->container->get(CancelRequestCollectionFactory::class)
        );

        $this->assertEquals(200, $response->getStatusCode());

        foreach ($tasks as $task) {
            $this->assertEquals('task-cancelled', $task->getState());
        }
    }
}
