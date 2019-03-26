<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\KernelExceptionLoggerEventListener;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

class KernelExceptionLoggerEventListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnKernelExceptionForSubRequest()
    {
        $event = $this->createGetResponseForExceptionEvent(
            new Request(),
            new \Exception(),
            KernelInterface::SUB_REQUEST
        );

        /* @var LoggerInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);

        $eventListener = new KernelExceptionLoggerEventListener($logger);
        $returnValue = $eventListener->onKernelException($event);

        $this->assertNull($returnValue);
        $this->assertFalse($event->hasResponse());
    }

    /**
     * @dataProvider onKernelExceptionDataProvider
     *
     * @param Request $request
     * @param \Exception $exception
     * @param string $expectedLogMessage
     * @param int $expectedResponseStatusCode
     */
    public function testOnKernelException(
        Request $request,
        \Exception $exception,
        string $expectedLogMessage,
        int $expectedResponseStatusCode
    ) {
        $event = $this->createGetResponseForExceptionEvent($request, $exception);

        /* @var LoggerInterface|MockInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->withArgs(function (string $message, array $context) use ($expectedLogMessage) {
                $this->assertEquals($expectedLogMessage, $message);
                $this->assertArrayHasKey('trace', $context);
                $this->assertNotEmpty($context['trace']);

                return true;
            });

        $eventListener = new KernelExceptionLoggerEventListener($logger);
        $returnValue = $eventListener->onKernelException($event);

        $this->assertNull($returnValue);
        $this->assertTrue($event->hasResponse());

        $response = $event->getResponse();

        if ($response instanceof Response) {
            $this->assertEquals($expectedResponseStatusCode, $response->getStatusCode());
        }
    }

    public function onKernelExceptionDataProvider(): array
    {
        return [
            'generic exception' => [
                'request' => new Request(),
                'exception' => new \Exception('Exception Message'),
                'expectedLogMessage' => '[Exception]: Exception Message',
                'expectedResponseStatusCode' => 500,
            ],
            'NotFoundHttpException' => [
                'request' => new Request(),
                'exception' => new NotFoundHttpException('Not Found'),
                'expectedLogMessage' =>
                    '[Symfony\Component\HttpKernel\Exception\NotFoundHttpException]: Not Found',
                'expectedResponseStatusCode' => 404,
            ],
            'TooManyRequestsHttpException' => [
                'request' => new Request(),
                'exception' => new TooManyRequestsHttpException(30, 'Too Many Requests'),
                'expectedLogMessage' =>
                    '[Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException]: Too Many Requests',
                'expectedResponseStatusCode' => 429,
            ],
        ];
    }

    private function createGetResponseForExceptionEvent(
        Request $request,
        \Exception $exception,
        int $requestType = KernelInterface::MASTER_REQUEST
    ): GetResponseForExceptionEvent {
        /* @var KernelInterface $kernel */
        $kernel = \Mockery::mock(KernelInterface::class);

        return new GetResponseForExceptionEvent($kernel, $request, $requestType, $exception);
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
