<?php

namespace Tests\WorkerBundle\Functional\Services;

use GuzzleHttp\Exception\ConnectException;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;

class WorkerServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var WorkerService
     */
    private $workerService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->workerService = $this->container->get(WorkerService::class);
    }

    public function testActivateWhenNotNew()
    {
        $worker = $this->workerService->get();
        $this->setWorkerState($worker, WorkerService::WORKER_ACTIVE_STATE);

        $this->assertEquals(0, $this->workerService->activate());
    }


    /**
     * @dataProvider activateDataProvider
     *
     * @param string|ConnectException $responseFixture
     * @param int $expectedReturnCode
     * @param string $expectedWorkerState
     */
    public function testActivate($responseFixture, $expectedReturnCode, $expectedWorkerState)
    {
        $this->setHttpFixtures([
            $responseFixture,
        ]);

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
        return [
            'success' => [
                'responseFixture' => 'HTTP/1.1 200 OK',
                'expectedReturnCode' => 0,
                'expectedWorkerState' => 'worker-awaiting-activation-verification',
            ],
            'failure http 404' => [
                'responseFixture' => 'HTTP/1.1 404 Not Found',
                'expectedReturnCode' => 404,
                'expectedWorkerState' => 'worker-new',
            ],
            'failure curl 28' => [
                'responseFixture' => ConnectExceptionFactory::create('CURL/28 Operation timed out.'),
                'expectedReturnCode' => 28,
                'expectedWorkerState' => 'worker-new',
            ],
        ];
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param bool $hasWorker
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
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function setWorkerState(ThisWorker $worker, $stateName)
    {
        $stateService = $this->container->get(StateService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $worker->setState($stateService->fetch($stateName));
        $entityManager->persist($worker);
        $entityManager->flush();
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function removeWorker()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entities = $entityManager->getRepository(ThisWorker::class)->findAll();
        if (!empty($entities)) {
            $entityManager->remove($entities[0]);
            $entityManager->flush();
        }
    }
}
