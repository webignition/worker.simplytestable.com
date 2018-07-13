<?php

namespace Tests\AppBundle\Functional\Controller;

use AppBundle\Resque\Job\TasksRequestJob;
use AppBundle\Services\Resque\QueueService;

/**
 * @group Controller/TasksController
 */
class TasksControllerTest extends AbstractControllerTest
{
    const RESQUE_QUEUE_NAME = 'tasks-request';

    /**
     * @var QueueService
     */
    private $resqueQueueService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->resqueQueueService = self::$container->get(QueueService::class);
        $this->clearRedis();
        $this->assertTrue($this->resqueQueueService->isEmpty(self::RESQUE_QUEUE_NAME));
    }

    public function testNotifyActionQueueEmpty()
    {
        $this->client->request('GET', $this->router->generate('tasks_notify'));
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }


    public function testNotifyActionQueueNotEmpty()
    {
        $this->resqueQueueService->enqueue(new TasksRequestJob());
        $this->assertPostConditions();

        $this->client->request('GET', $this->router->generate('tasks_notify'));
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * {@inheritdoc}
     */
    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertFalse($this->resqueQueueService->isEmpty(self::RESQUE_QUEUE_NAME));
        $this->assertSame(1, $this->resqueQueueService->getQueueLength(self::RESQUE_QUEUE_NAME));
    }
}
