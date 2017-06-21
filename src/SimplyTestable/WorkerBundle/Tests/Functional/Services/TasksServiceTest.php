<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Services;

use GuzzleHttp\Post\PostBodyInterface;
use SimplyTestable\WorkerBundle\Exception\Services\TasksService\RequestException;
use SimplyTestable\WorkerBundle\Services\TasksService;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\ConnectExceptionFactory;

class TasksServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function getServicesToMock()
    {
        return [
            'logger',
            'simplytestable.services.taskservice',
        ];
    }

    public function testGetMaxTasksRequestFactor()
    {
        $this->assertEquals(
            $this->container->getParameter('max_tasks_request_factor'),
            $this->getTasksService()->getMaxTasksRequestFactor()
        );
    }

    public function testGetWorkerProcessCount()
    {
        $this->assertEquals(
            $this->container->getParameter('worker_process_count'),
            $this->getTasksService()->getWorkerProcessCount()
        );
    }

    public function testRequestNotWithinThreshold()
    {
        $this->getTaskService()
            ->shouldReceive('getInCompleteCount')
            ->andReturn($this->getTasksService()->getWorkerProcessCount() + 1);

        $this->assertFalse($this->getTasksService()->request());
    }

    /**
     * @dataProvider requestHttpRequestFailureDataProvider
     *
     * @param $httpResponseFixture
     * @param $expectedLogErrorMessage
     * @param $expectedException
     */
    public function testRequestHttpRequestFailure($httpResponseFixture, $expectedLogErrorMessage, $expectedException)
    {
        $this->getTaskService()
            ->shouldReceive('getInCompleteCount')
            ->andReturn($this->getTasksService()->getWorkerProcessCount());

        $this->setHttpFixtures([
            $httpResponseFixture
        ]);

        $this->setExpectedException(
            $expectedException['class'],
            $expectedException['message'],
            $expectedException['code']
        );

        $this->getTasksService()->request();

        $this->container->get('logger')
            ->shouldHaveReceived('error')
            ->with($expectedLogErrorMessage);
    }

    /**
     * @return array
     */
    public function requestHttpRequestFailureDataProvider()
    {
        return [
            'http-400' => [
                'httpResponseFixture' => 'HTTP/1.1 400',
                'expectedLogErrorMessage' => 'TasksService:request:GuzzleHttp\Exception\ClientException [400]',
                'expectedException' => [
                    'class' => RequestException::class,
                    'message' => 'GuzzleHttp\Exception\ClientException',
                    'code' => 400,
                ],
            ],
            'http-500' => [
                'httpResponseFixture' => 'HTTP/1.1 500',
                'expectedLogErrorMessage' => 'TasksService:request:GuzzleHttp\Exception\ServerException [500]',
                'expectedException' => [
                    'class' => RequestException::class,
                    'message' => 'GuzzleHttp\Exception\ServerException',
                    'code' => 500,
                ],
            ],
            'curl-28' => [
                'httpResponseFixture' => ConnectExceptionFactory::create('CURL/28 Operation timed out.'),
                'expectedLogErrorMessage' => 'TasksService:request:GuzzleHttp\Exception\ConnectException [28]',
                'expectedException' => [
                    'class' => RequestException::class,
                    'message' => 'GuzzleHttp\Exception\ConnectException',
                    'code' => 28,
                ],
            ],
        ];
    }

    /**
     * @dataProvider requestSuccessDataProvider
     *
     * @param int $requestedLimit
     */
    public function testRequestSuccess($requestedLimit, $expectedLimit)
    {
        $this->getTaskService()
            ->shouldReceive('getInCompleteCount')
            ->andReturn($this->getTasksService()->getWorkerProcessCount());

        $this->setHttpFixtures([
            'HTTP/1.1 200 OK'
        ]);

        $this->assertTrue(
            $this->getTasksService()->request($requestedLimit)
        );

        /**
         * @var PostBodyInterface
         */
        $body = $this->getHttpClientService()->getHistory()->getLastRequest()->getBody();

        $this->assertEquals($expectedLimit, $body->getFields()['limit']);
    }

    /**
     * @return array
     */
    public function requestSuccessDataProvider()
    {
        return [
            'null' => [
                'requestedLimit' => null,
                'expectedLimit' => 5,
            ],
            'zero' => [
                'requestedLimit' => 0,
                'expectedLimit' => 1,
            ],
            'less than hard limit' => [
                'requestedLimit' => 4,
                'expectedLimit' => 4,
            ],
            'greater than hard limit' => [
                'requestedLimit' => 6,
                'expectedLimit' => 5,
            ],
        ];
    }

    /**
     * @return TasksService
     */
    private function getTasksService()
    {
        return $this->container->get('simplytestable.services.tasksservice');
    }
}
