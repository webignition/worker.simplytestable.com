<?php

namespace Tests\WorkerBundle\Functional\Services;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use SimplyTestable\WorkerBundle\Exception\Services\TasksService\RequestException;
use SimplyTestable\WorkerBundle\Services\FooHttpClientService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;
use Tests\WorkerBundle\Services\TestFooHttpClientService;
use Tests\WorkerBundle\Utility\File;

class TasksServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TasksService
     */
    private $tasksService;

    /**
     * @var TestFooHttpClientService
     */
    private $fooHttpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->tasksService = $this->container->get(TasksService::class);
        $this->fooHttpClientService = $this->container->get(FooHttpClientService::class);
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
     * @param array $httpFixtures
     * @param string $expectedLogErrorMessage
     * @param array $expectedException
     *
     * @throws GuzzleException
     */
    public function testRequestHttpRequestFailure(array $httpFixtures, $expectedLogErrorMessage, $expectedException)
    {
        $this->fooHttpClientService->appendFixtures($httpFixtures);

        try {
            $this->tasksService->request();
        } catch (RequestException $requestException) {
            $this->assertEquals($expectedException['class'], get_class($requestException));
            $this->assertEquals($expectedException['message'], $requestException->getMessage());
            $this->assertEquals($expectedException['code'], $requestException->getCode());
        }

        $lastLogLine = File::tail($this->container->get('kernel')->getLogDir() . '/test.log');
        $this->assertRegExp('/' . preg_quote($expectedLogErrorMessage) .'/', $lastLogLine);
    }

    /**
     * @return array
     */
    public function requestHttpRequestFailureDataProvider()
    {
        $internalServerErrorResponse = new Response(500);
        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28 Operation timed out.');

        return [
            'http-400' => [
                'httpFixtures' => [
                    new Response(400),
                ],
                'expectedLogErrorMessage' => 'TasksService:request:GuzzleHttp\Exception\ClientException [400]',
                'expectedException' => [
                    'class' => RequestException::class,
                    'message' => 'GuzzleHttp\Exception\ClientException',
                    'code' => 400,
                ],
            ],
            'http-500' => [
                'httpFixtures' => [
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                ],
                'expectedLogErrorMessage' => 'TasksService:request:GuzzleHttp\Exception\ServerException [500]',
                'expectedException' => [
                    'class' => RequestException::class,
                    'message' => 'GuzzleHttp\Exception\ServerException',
                    'code' => 500,
                ],
            ],
            'curl-28' => [
                'httpFixtures' => [
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                ],
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
     *
     * @throws GuzzleException
     * @throws RequestException
     */
    public function testRequestSuccess($requestedLimit, $expectedLimit)
    {
        $this->fooHttpClientService->appendFixtures([
            new Response(200),
        ]);

        $this->assertTrue(
            $this->tasksService->request($requestedLimit)
        );

        $lastRequest = $this->fooHttpClientService->getHistory()->getLastRequest();
        $postedData = [];
        parse_str(urldecode($lastRequest->getBody()->getContents()), $postedData);

        $this->assertEquals($expectedLimit, $postedData['limit']);
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

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->fooHttpClientService->getMockHandler()->count());
    }

}
