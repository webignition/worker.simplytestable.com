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
     * @dataProvider getDataProvider
     *
     * @param bool $hasWorker
     *
     * @throws ORMException
     */
    public function testGet($hasWorker)
    {
        if (!$hasWorker) {
            $this->removeWorker();
        }

        $this->workerService->get();
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            'create' => [
                'hasWorker' => false,
            ],
            'get' => [
                'hasWorker' => true,
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

    /**
     * @param ThisWorker $worker
     * @param string $stateName
     *
     * @throws ORMException
     */
    private function setWorkerState(ThisWorker $worker, $stateName)
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $worker->setState($stateName);
        $entityManager->persist($worker);
        $entityManager->flush();
    }

    /**
     * @throws ORMException
     */
    private function removeWorker()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $entities = $entityManager->getRepository(ThisWorker::class)->findAll();
        if (!empty($entities)) {
            $entityManager->remove($entities[0]);
            $entityManager->flush();
        }
    }

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->httpMockHandler->count());
    }
}
