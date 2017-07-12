<?php

namespace Tests\WorkerBundle\Functional\Services;

use GuzzleHttp\Exception\ConnectException;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;

class WorkerServiceTest extends BaseSimplyTestableTestCase
{
    public function testActivateWhenNotNew()
    {
        $worker = $this->getWorkerService()->get();
        $this->setWorkerState($worker, WorkerService::WORKER_ACTIVE_STATE);

        $this->assertEquals(0, $this->getWorkerService()->activate());
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

        $worker = $this->getWorkerService()->get();
        $this->setWorkerState($worker, WorkerService::WORKER_NEW_STATE);

        $this->assertEquals(
            $expectedReturnCode,
            $this->getWorkerService()->activate()
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

        $this->getWorkerService()->get();
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
        $worker = $this->getWorkerService()->get();
        $this->setWorkerState($worker, $stateName);

        $this->assertEquals($expectedIsActive, $this->getWorkerService()->isActive());
        $this->assertEquals($expectedIsMaintenanceReadOnly, $this->getWorkerService()->isMaintenanceReadOnly());
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
        $worker = $this->getWorkerService()->get();
        $this->setWorkerState($worker, $stateName);

        $this->getWorkerService()->setActive();

        $this->assertEquals(WorkerService::WORKER_ACTIVE_STATE, $worker->getState());
    }

    /**
     * @dataProvider stateNameDataProvider
     *
     * @param string $stateName
     */
    public function testSetReadOnly($stateName)
    {
        $worker = $this->getWorkerService()->get();
        $this->setWorkerState($worker, $stateName);

        $this->getWorkerService()->setReadOnly();
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
        $worker = $this->getWorkerService()->get();
        $this->setWorkerState($worker, $stateName);

        $this->getWorkerService()->verify();
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
     */
    private function setWorkerState(ThisWorker $worker, $stateName)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $worker->setState($stateService->fetch($stateName));
        $this->getEntityManager()->persist($worker);
        $this->getEntityManager()->flush();
    }

    private function removeWorker()
    {
        $entities = $this->getEntityManager()->getRepository(ThisWorker::class)->findAll();
        if (!empty($entities)) {
            $this->getEntityManager()->remove($entities[0]);
            $this->getEntityManager()->flush();
        }
    }
}
