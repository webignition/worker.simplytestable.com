<?php

namespace Tests\WorkerBundle\Functional\Services;

use Doctrine\ORM\ORMException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;
use Tests\WorkerBundle\Services\HttpMockHandler;

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
        $this->setWorkerState($worker, WorkerService::WORKER_ACTIVE_STATE);

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
        $this->setWorkerState($worker, WorkerService::WORKER_NEW_STATE);

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
                'expectedWorkerState' => 'worker-awaiting-activation-verification',
            ],
            'failure http 404' => [
                'httpFixtures' => [
                    new Response(404),
                ],
                'expectedReturnCode' => 404,
                'expectedWorkerState' => 'worker-new',
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
                'expectedWorkerState' => 'worker-new',
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
     * @dataProvider isStateDataProvider
     *
     * @param string $stateName
     * @param bool $expectedIsActive
     * @param bool $expectedIsMaintenanceReadOnly
     *
     * @throws ORMException
     */
    public function testIsState($stateName, $expectedIsActive, $expectedIsMaintenanceReadOnly)
    {
        $worker = $this->workerService->get();
        $this->setWorkerState($worker, $stateName);

        $this->assertEquals($expectedIsActive, $this->workerService->isActive());
        $this->assertEquals($expectedIsMaintenanceReadOnly, $this->workerService->isMaintenanceReadOnly());
    }

    /**
     * @return array
     */
    public function isStateDataProvider()
    {
        return [
            'active' => [
                'stateName' => 'worker-active',
                'expectedIsActive' => true,
                'expectedIsMaintenanceReadOnly' => false,
            ],
            'awaiting-activation-verification' => [
                'stateName' => 'worker-awaiting-activation-verification',
                'expectedIsActive' => false,
                'expectedIsMaintenanceReadOnly' => false,
            ],
            'new' => [
                'stateName' => 'worker-new',
                'expectedIsActive' => false,
                'expectedIsMaintenanceReadOnly' => false,
            ],
            'maintenance-read-only' => [
                'stateName' => 'worker-maintenance-read-only',
                'expectedIsActive' => false,
                'expectedIsMaintenanceReadOnly' => true,
            ],
        ];
    }

    /**
     * @return array
     */
    public function isMaintenanceReadOnlyDataProvider()
    {
        return [
            'active' => [
                'stateName' => 'worker-active',
                'expectedIsMaintenanceReadOnly' => false,
            ],
            'awaiting-activation-verification' => [
                'stateName' => 'worker-awaiting-activation-verification',
                'expectedIsMaintenanceReadOnly' => false,
            ],
            'new' => [
                'stateName' => 'worker-new',
                'expectedIsMaintenanceReadOnly' => false,
            ],
            'maintenance-read-only' => [
                'stateName' => 'worker-maintenance-read-only',
                'expectedIsActive' => false,
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

        $this->assertEquals(WorkerService::WORKER_ACTIVE_STATE, $worker->getState());
    }

    /**
     * @dataProvider stateNameDataProvider
     *
     * @param string $stateName
     *
     * @throws ORMException
     */
    public function testSetReadOnly($stateName)
    {
        $worker = $this->workerService->get();
        $this->setWorkerState($worker, $stateName);

        $this->workerService->setReadOnly();
        $this->assertEquals(WorkerService::WORKER_MAINTENANCE_READ_ONLY_STATE, $worker->getState());
    }

    /**
     * @return array
     */
    public function stateNameDataProvider()
    {
        return [
            'new' => [
                'worker-new',
            ],
            'awaiting-activation-verification' => [
                'worker-awaiting-activation-verification',
            ],
            'active' => [
                'worker-active',
            ],
            'maintenance-read-only' => [
                'worker-maintenance-read-only',
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
                'stateName' => 'worker-new',
                'expectedWorkerState' => 'worker-new',
            ],
            'awaiting-activation-verification' => [
                'stateName' => 'worker-awaiting-activation-verification',
                'expectedWorkerState' => 'worker-active',
            ],
            'active' => [
                'stateName' => 'worker-active',
                'expectedWorkerState' => 'worker-active',
            ],
            'maintenance-read-only' => [
                'stateName' => 'worker-maintenance-read-only',
                'expectedWorkerState' => 'worker-maintenance-read-only',
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
        $stateService = self::$container->get(StateService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $worker->setState($stateService->fetch($stateName));
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
