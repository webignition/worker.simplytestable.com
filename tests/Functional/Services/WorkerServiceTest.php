<?php

namespace App\Tests\Functional\Services;

use Doctrine\ORM\ORMException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use App\Entity\ThisWorker;
use App\Services\WorkerService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Services\HttpMockHandler;

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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->workerService = self::$container->get(WorkerService::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
    }

    /**
     * @throws GuzzleException
     * @throws ORMException
     */
    public function testActivateWhenNotNew()
    {
        $worker = $this->workerService->get();
        $this->setWorkerState($worker, ThisWorker::STATE_ACTIVE);

        $this->assertEquals(0, $this->workerService->activate());
    }

    /**
     * @dataProvider activateDataProvider
     *
     * @param array $httpFixtures
     * @param int $expectedReturnCode
     * @param string $expectedWorkerState
     *
     * @throws GuzzleException
     * @throws ORMException
     */
    public function testActivate(array $httpFixtures, $expectedReturnCode, $expectedWorkerState)
    {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $worker = $this->workerService->get();
        $this->setWorkerState($worker, ThisWorker::STATE_NEW);

        $this->assertEquals(
            $expectedReturnCode,
            $this->workerService->activate()
        );

        $this->assertEquals($expectedWorkerState, $worker->getState());
    }

    /**
     * @return array
     */
    public function activateDataProvider()
    {
        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28 Operation timed out.');

        return [
            'success' => [
                'httpFixtures' => [
                    new Response(200),
                ],
                'expectedReturnCode' => 0,
                'expectedWorkerState' => ThisWorker::STATE_AWAITING_ACTIVATION_VERIFICATION,
            ],
            'failure http 404' => [
                'httpFixtures' => [
                    new Response(404),
                ],
                'expectedReturnCode' => 404,
                'expectedWorkerState' => ThisWorker::STATE_NEW,
            ],
            'failure curl 28' => [
                'httpFixtures' => [
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                    $curl28ConnectException,
                ],
                'expectedReturnCode' => 28,
                'expectedWorkerState' => ThisWorker::STATE_NEW,
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
     * @dataProvider stateNameDataProvider
     *
     * @param string $stateName
     *
     * @throws ORMException
     */
    public function testSetActive($stateName)
    {
        $worker = $this->workerService->get();
        $this->setWorkerState($worker, $stateName);

        $this->workerService->setActive();

        $this->assertEquals(ThisWorker::STATE_ACTIVE, $worker->getState());
    }

    /**
     * @return array
     */
    public function stateNameDataProvider()
    {
        return [
            'new' => [
                ThisWorker::STATE_NEW,
            ],
            'awaiting-activation-verification' => [
                ThisWorker::STATE_AWAITING_ACTIVATION_VERIFICATION,
            ],
            'active' => [
                ThisWorker::STATE_ACTIVE,
            ],
            'maintenance-read-only' => [
                ThisWorker::STATE_MAINTENANCE_READ_ONLY,
            ],
        ];
    }

    /**
     * @dataProvider verifyDataProvider
     *
     * @param string $stateName
     * @param string $expectedWorkerState
     *
     * @throws ORMException
     */
    public function testVerify($stateName, $expectedWorkerState)
    {
        $worker = $this->workerService->get();
        $this->setWorkerState($worker, $stateName);

        $this->workerService->verify();
        $this->assertEquals($expectedWorkerState, $worker->getState());
    }

    /**
     * @return array
     */
    public function verifyDataProvider()
    {
        return [
            'new' => [
                'stateName' => ThisWorker::STATE_NEW,
                'expectedWorkerState' => ThisWorker::STATE_NEW,
            ],
            'awaiting-activation-verification' => [
                'stateName' => ThisWorker::STATE_AWAITING_ACTIVATION_VERIFICATION,
                'expectedWorkerState' => ThisWorker::STATE_ACTIVE,
            ],
            'active' => [
                'stateName' => ThisWorker::STATE_ACTIVE,
                'expectedWorkerState' => ThisWorker::STATE_ACTIVE,
            ],
            'maintenance-read-only' => [
                'stateName' => ThisWorker::STATE_MAINTENANCE_READ_ONLY,
                'expectedWorkerState' => ThisWorker::STATE_MAINTENANCE_READ_ONLY,
            ],
        ];
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
