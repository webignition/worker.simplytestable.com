<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use GuzzleHttp\Psr7\Response;
use App\Exception\Services\TasksService\RequestException;
use App\Services\TasksService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Utility\File;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class TasksServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TasksService
     */
    private $tasksService;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistoryContainer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->tasksService = self::$container->get(TasksService::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
    }

    public function testGetMaxTasksRequestFactor()
    {
        $this->assertEquals(
            getenv('MAX_TASKS_REQUEST_FACTOR'),
            $this->tasksService->getMaxTasksRequestFactor()
        );
    }

    public function testGetWorkerProcessCount()
    {
        $this->assertEquals(
            getenv('WORKER_PROCESS_COUNT'),
            $this->tasksService->getWorkerProcessCount()
        );
    }

    /**
     * @dataProvider requestHttpRequestFailureDataProvider
     */
    public function testRequestHttpRequestFailure(
        array $httpFixtures,
        string $expectedLogErrorMessage,
        array $expectedException
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        try {
            $this->tasksService->request();
        } catch (RequestException $requestException) {
            $this->assertEquals($expectedException['class'], get_class($requestException));
            $this->assertEquals($expectedException['message'], $requestException->getMessage());
            $this->assertEquals($expectedException['code'], $requestException->getCode());
        }

        $lastLogLine = File::tail(self::$container->get('kernel')->getLogDir() . '/test.log');
        $this->assertRegExp('/' . preg_quote($expectedLogErrorMessage) .'/', $lastLogLine);
    }

    public function requestHttpRequestFailureDataProvider(): array
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
                'httpFixtures' => array_fill(0, 6, $internalServerErrorResponse),
                'expectedLogErrorMessage' => 'TasksService:request:GuzzleHttp\Exception\ServerException [500]',
                'expectedException' => [
                    'class' => RequestException::class,
                    'message' => 'GuzzleHttp\Exception\ServerException',
                    'code' => 500,
                ],
            ],
            'curl-28' => [
                'httpFixtures' => array_fill(0, 6, $curl28ConnectException),
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
     */
    public function testRequestSuccess(?int $requestedLimit, int $expectedLimit)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200),
        ]);

        $this->assertTrue(
            $this->tasksService->request($requestedLimit)
        );

        $lastRequest = $this->httpHistoryContainer->getLastRequest();
        $this->assertEquals('application/x-www-form-urlencoded', $lastRequest->getHeaderLine('content-type'));

        $postedData = [];
        parse_str(urldecode($lastRequest->getBody()->getContents()), $postedData);

        $this->assertEquals(
            [
                'worker_hostname' => self::$container->getParameter('hostname'),
                'worker_token' => self::$container->getParameter('token'),
                'limit' => $expectedLimit,
            ],
            $postedData
        );
    }

    public function requestSuccessDataProvider(): array
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

        $this->assertEquals(0, $this->httpMockHandler->count());
    }
}
