<?php

namespace SimplyTestable\WorkerBundle\Tests\Services;

use GuzzleHttp\Post\PostBodyInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Exception\Services\TasksService\RequestException;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class TasksServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @inheritdoc
     */
    protected static function getMockServices()
    {
        return [
            'logger' => LoggerInterface::class,
            'simplytestable.services.taskservice' => TaskService::class,
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

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            $httpResponseFixture
        ]));

        $this->container->get('logger')
            ->shouldReceive('error')
            ->with($expectedLogErrorMessage);

        $this->setExpectedException(
            $expectedException['class'],
            $expectedException['message'],
            $expectedException['code']
        );

        $this->getTasksService()->request();
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
                'httpResponseFixture' => 'CURL/28 Operation timed out.',
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

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            'HTTP/1.1 200 OK'
        ]));

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
}
