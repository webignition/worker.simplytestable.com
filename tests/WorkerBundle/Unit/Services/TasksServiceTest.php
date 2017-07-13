<?php

namespace Tests\WorkerBundle\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\Response;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Exception\Services\TasksService\RequestException;
use SimplyTestable\WorkerBundle\Services\CoreApplicationRouter;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use SimplyTestable\WorkerBundle\Services\UrlService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;

class TasksServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testRequestNotWithinThreshold()
    {
        $workerProcessCount = 1;

        $taskService = \Mockery::mock(TaskService::class);
        $taskService
            ->shouldReceive('getInCompleteCount')
            ->andReturn($workerProcessCount + 1);

        $tasksService = $this->createTasksService([
            'taskService' => $taskService,
        ]);

        $tasksService->setWorkerProcessCount($workerProcessCount);

        $this->assertFalse($tasksService->request());
    }

    /**
     * @dataProvider requestHttpRequestFailureDataProvider
     *
     * @param \Exception $responseException
     * @param string $expectedLogErrorMessage
     * @param array $expectedException
     */
    public function testRequestHttpRequestFailure(
        \Exception $responseException,
        $expectedLogErrorMessage,
        $expectedException
    ) {
        $requestUrl = 'http://test.app.simplytestable.com/worker/tasks/request';

        $workerProcessCount = 1;

        $taskService = \Mockery::mock(TaskService::class);
        $taskService
            ->shouldReceive('getInCompleteCount')
            ->andReturn(0);

        $coreApplicationRouter = \Mockery::mock(CoreApplicationRouter::class);
        $coreApplicationRouter
            ->shouldReceive('generate')
            ->with('tasks_request')
            ->andReturn($requestUrl);

        $urlService = \Mockery::mock(UrlService::class);
        $urlService
            ->shouldReceive('prepare')
            ->with($requestUrl)
            ->andReturn($requestUrl);

        $thisWorker = new ThisWorker();
        $thisWorker->setHostname('test.worker.simplytestable.com');
        $thisWorker->setActivationToken('token');

        $workerService = \Mockery::mock(WorkerService::class);
        $workerService
            ->shouldReceive('get')
            ->andReturn($thisWorker);

        $httpRequest = \Mockery::mock(RequestInterface::class);

        $httpClient = \Mockery::mock(Client::class);
        $httpClient
            ->shouldReceive('send')
            ->andThrow($responseException);

        $httpClientService = \Mockery::mock(HttpClientService::class);
        $httpClientService
            ->shouldReceive('postRequest')
            ->with($requestUrl, [
                'body' => [
                    'worker_hostname' => $thisWorker->getHostname(),
                    'worker_token' => $thisWorker->getActivationToken(),
                    'limit' => 0
                ],
            ])
            ->andReturn($httpRequest);

        $httpClientService
            ->shouldReceive('get')
            ->andReturn($httpClient);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->with($expectedLogErrorMessage);

        $tasksService = $this->createTasksService([
            'taskService' => $taskService,
            'coreApplicationRouter' => $coreApplicationRouter,
            'urlService' => $urlService,
            'workerService' => $workerService,
            'httpClientService' => $httpClientService,
            'logger' => $logger,
        ]);

        $tasksService->setWorkerProcessCount($workerProcessCount);

        $this->expectException($expectedException['class']);
        $this->expectExceptionMessage($expectedException['message']);
        $this->expectExceptionCode($expectedException['code']);

        $tasksService->request();
    }

    /**
     * @return array
     */
    public function requestHttpRequestFailureDataProvider()
    {
        /* @var MockInterface|RequestInterface $emptyRequest */
        $emptyRequest = \Mockery::mock(RequestInterface::class);

        return [
            'http-400' => [
                'responseException' => new GuzzleRequestException(
                    'message',
                    $emptyRequest,
                    new Response(400)
                ),
                'expectedLogErrorMessage' => 'TasksService:request:GuzzleHttp\Exception\RequestException [400]',
                'expectedException' => [
                    'class' => RequestException::class,
                    'message' => 'GuzzleHttp\Exception\RequestException',
                    'code' => 400,
                ],
            ],
            'http-500' => [
                'responseException' => new GuzzleRequestException(
                    'message',
                    $emptyRequest,
                    new Response(500)
                ),
                'expectedLogErrorMessage' => 'TasksService:request:GuzzleHttp\Exception\RequestException [500]',
                'expectedException' => [
                    'class' => RequestException::class,
                    'message' => 'GuzzleHttp\Exception\RequestException',
                    'code' => 500,
                ],
            ],
            'curl-28' => [
                'responseException' => ConnectExceptionFactory::create('CURL/28 Operation timed out.'),
                'expectedLogErrorMessage' => 'TasksService:request:GuzzleHttp\Exception\ConnectException [28]',
                'expectedException' => [
                    'class' => RequestException::class,
                    'message' => 'GuzzleHttp\Exception\ConnectException',
                    'code' => 28,
                ],
            ],
        ];
    }

    public function testRequestSuccess()
    {
        $requestUrl = 'http://test.app.simplytestable.com/worker/tasks/request';

        $workerProcessCount = 1;

        $taskService = \Mockery::mock(TaskService::class);
        $taskService
            ->shouldReceive('getInCompleteCount')
            ->andReturn(0);

        $coreApplicationRouter = \Mockery::mock(CoreApplicationRouter::class);
        $coreApplicationRouter
            ->shouldReceive('generate')
            ->with('tasks_request')
            ->andReturn($requestUrl);

        $urlService = \Mockery::mock(UrlService::class);
        $urlService
            ->shouldReceive('prepare')
            ->with($requestUrl)
            ->andReturn($requestUrl);

        $thisWorker = new ThisWorker();
        $thisWorker->setHostname('test.worker.simplytestable.com');
        $thisWorker->setActivationToken('token');

        $workerService = \Mockery::mock(WorkerService::class);
        $workerService
            ->shouldReceive('get')
            ->andReturn($thisWorker);

        $httpRequest = \Mockery::mock(RequestInterface::class);

        $httpClient = \Mockery::mock(Client::class);
        $httpClient
            ->shouldReceive('send')
            ->andReturn(new Response(200));

        $httpClientService = \Mockery::mock(HttpClientService::class);
        $httpClientService
            ->shouldReceive('postRequest')
            ->with($requestUrl, [
                'body' => [
                    'worker_hostname' => $thisWorker->getHostname(),
                    'worker_token' => $thisWorker->getActivationToken(),
                    'limit' => 0
                ],
            ])
            ->andReturn($httpRequest);

        $httpClientService
            ->shouldReceive('get')
            ->andReturn($httpClient);

        $tasksService = $this->createTasksService([
            'taskService' => $taskService,
            'coreApplicationRouter' => $coreApplicationRouter,
            'urlService' => $urlService,
            'workerService' => $workerService,
            'httpClientService' => $httpClientService,
        ]);

        $tasksService->setWorkerProcessCount($workerProcessCount);

        $this->assertTrue($tasksService->request());
    }

    /**
     * @param array $services
     *
     * @return TasksService
     */
    private function createTasksService($services = [])
    {
        if (!isset($services['logger'])) {
            $services['logger'] = \Mockery::mock(LoggerInterface::class);
        }

        if (!isset($services['urlService'])) {
            $services['urlService'] = \Mockery::mock(UrlService::class);
        }

        if (!isset($services['coreApplicationRouter'])) {
            $services['coreApplicationRouter'] = \Mockery::mock(CoreApplicationRouter::class);
        }

        if (!isset($services['workerService'])) {
            $services['workerService'] = \Mockery::mock(WorkerService::class);
        }

        if (!isset($services['httpClientService'])) {
            $services['httpClientService'] = \Mockery::mock(HttpClientService::class);
        }

        if (!isset($services['taskService'])) {
            $services['taskService'] = \Mockery::mock(TaskService::class);
        }

        return new TasksService(
            $services['logger'],
            $services['urlService'],
            $services['coreApplicationRouter'],
            $services['workerService'],
            $services['httpClientService'],
            $services['taskService']
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
