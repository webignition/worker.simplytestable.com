<?php

namespace App\Tests\Functional\Services;

use App\Services\ApplicationState;
use Doctrine\ORM\ORMException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use App\Entity\ThisWorker;
use App\Services\WorkerService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Services\HttpMockHandler;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class WorkerServiceTest extends AbstractBaseTestCase
{
    /**
     * @var WorkerService
     */
    private $workerService;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistoryContainer;

    /**
     * @var ApplicationState
     */
    private $applicationState;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->workerService = self::$container->get(WorkerService::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
        $this->applicationState = self::$container->get(ApplicationState::class);
    }

    /**
     * @throws GuzzleException
     */
    public function testActivateWhenNotNew()
    {
        $this->applicationState->set(ApplicationState::STATE_ACTIVE);

        $this->assertEquals(0, $this->workerService->activate());
    }

    /**
     * @dataProvider activateDataProvider
     *
     * @param array $httpFixtures
     * @param int $expectedReturnCode
     * @param string $expectedApplicationState
     *
     * @throws GuzzleException
     */
    public function testActivate(array $httpFixtures, int $expectedReturnCode, string $expectedApplicationState)
    {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $this->applicationState->set(ApplicationState::STATE_NEW);

        $this->assertEquals(
            $expectedReturnCode,
            $this->workerService->activate()
        );

        $this->assertEquals($expectedApplicationState, $this->applicationState->get());

        $lastRequest = $this->httpHistoryContainer->getLastRequest();
        $this->assertEquals('application/x-www-form-urlencoded', $lastRequest->getHeaderLine('content-type'));

        $postedData = [];
        parse_str(urldecode($lastRequest->getBody()->getContents()), $postedData);

        $this->assertEquals(
            [
                'hostname' => self::$container->getParameter('hostname'),
                'token' => self::$container->getParameter('token'),
            ],
            $postedData
        );
    }

    public function activateDataProvider(): array
    {
        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28 Operation timed out.');

        return [
            'success' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'expectedReturnCode' => 0,
                'expectedApplicationState' => ApplicationState::STATE_AWAITING_ACTIVATION_VERIFICATION,
            ],
            'failure http 404' => [
                'httpFixtures' => [
                    new Response(404),
                ],
                'expectedReturnCode' => 404,
                'expectedApplicationState' => ApplicationState::STATE_NEW,
            ],
            'failure curl 28' => [
                'httpFixtures' => array_fill(0, 6, $curl28ConnectException),
                'expectedReturnCode' => 28,
                'expectedApplicationState' => ApplicationState::STATE_NEW,
            ],
        ];
    }

    /**
     * @dataProvider verifyDataProvider
     *
     * @param string $applicationState
     * @param string $expectedApplicationState
     */
    public function testVerify(string $applicationState, string $expectedApplicationState)
    {
        $this->applicationState->set($applicationState);

        $this->workerService->verify();
        $this->assertEquals($expectedApplicationState, $this->applicationState->get());
    }

    public function verifyDataProvider(): array
    {
        return [
            'new' => [
                'applicationState' => ThisWorker::STATE_NEW,
                'expectedApplicationState' => ThisWorker::STATE_NEW,
            ],
            'awaiting-activation-verification' => [
                'applicationState' => ThisWorker::STATE_AWAITING_ACTIVATION_VERIFICATION,
                'expectedApplicationState' => ThisWorker::STATE_ACTIVE,
            ],
            'active' => [
                'applicationState' => ThisWorker::STATE_ACTIVE,
                'expectedApplicationState' => ThisWorker::STATE_ACTIVE,
            ],
        ];
    }

    public function testGetHostname()
    {
        $this->assertSame(self::$container->getParameter('hostname'), $this->workerService->getHostname());
    }

    public function testGetToken()
    {
        $this->assertSame(self::$container->getParameter('token'), $this->workerService->getToken());
    }

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->httpMockHandler->count());
    }
}
