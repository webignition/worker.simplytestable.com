<?php

namespace Tests\WorkerBundle\Functional\Services;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Post\PostBodyInterface;
use SimplyTestable\WorkerBundle\Exception\Services\TasksService\RequestException;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;
use Tests\WorkerBundle\Utility\File;

class TasksServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TasksService
     */
    private $tasksService;

    protected function setUp()
    {
        parent::setUp();
        $this->tasksService = $this->container->get(TasksService::class);
    }

    public function testGetMaxTasksRequestFactor()
    {
        $this->assertEquals(
            $this->container->getParameter('max_tasks_request_factor'),
            $this->tasksService->getMaxTasksRequestFactor()
        );
    }

    public function testGetWorkerProcessCount()
    {
        $this->assertEquals(
            $this->container->getParameter('worker_process_count'),
            $this->tasksService->getWorkerProcessCount()
        );
    }

    /**
     * @dataProvider requestHttpRequestFailureDataProvider
     *
     * @param string|ConnectException $httpResponseFixture
     * @param string $expectedLogErrorMessage
     * @param array $expectedException
     */
    public function testRequestHttpRequestFailure($httpResponseFixture, $expectedLogErrorMessage, $expectedException)
    {
        $this->removeAllTasks();

        $this->setHttpFixtures([
            $httpResponseFixture
        ]);

        try {
            $this->tasksService->request();
        } catch (\Exception $exception) {
            $this->assertEquals($expectedException['class'], get_class($exception));
            $this->assertEquals($expectedException['message'], $exception->getMessage());
            $this->assertEquals($expectedException['code'], $exception->getCode());
        }

        $lastLogLine = File::tail($this->container->get('kernel')->getLogDir() . '/test.log');
        $this->assertRegExp('/' . preg_quote($expectedLogErrorMessage) .'/', $lastLogLine);
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
     * @param int $expectedLimit
     */
    public function testRequestSuccess($requestedLimit, $expectedLimit)
    {
        $this->removeAllTasks();

        $this->setHttpFixtures([
            'HTTP/1.1 200 OK'
        ]);

        $this->assertTrue(
            $this->tasksService->request($requestedLimit)
        );

        /**
         * @var PostBodyInterface
         */
        $body = $this->container->get(HttpClientService::class)->getHistory()->getLastRequest()->getBody();

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
                'expectedLimit' => 10,
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
                'requestedLimit' => 11,
                'expectedLimit' => 10,
            ],
        ];
    }
}
