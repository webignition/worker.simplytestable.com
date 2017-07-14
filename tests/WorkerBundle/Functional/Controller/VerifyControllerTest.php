<?php

namespace Tests\WorkerBundle\Functional\Controller;

use SimplyTestable\WorkerBundle\Controller\VerifyController;
use SimplyTestable\WorkerBundle\Entity\State;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Services\Request\Factory\VerifyRequestFactory;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class VerifyControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var VerifyController
     */
    private $verifyController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->verifyController = new VerifyController();
    }

    public function testIndexActionInMaintenanceReadOnlyMode()
    {
        $this->expectException(ServiceUnavailableHttpException::class);

        $request = new Request();
        $request->request = new ParameterBag();
        $this->container->get('request_stack')->push($request);

        $workerService = $this->container->get(WorkerService::class);
        $workerService->setReadOnly();

        $this->verifyController->indexAction(
            $workerService,
            $this->container->get(VerifyRequestFactory::class)
        );
    }

    public function testIndexActionWithInvalidRequest()
    {
        $this->expectException(BadRequestHttpException::class);

        $request = new Request();
        $request->request = new ParameterBag();
        $this->container->get('request_stack')->push($request);

        $this->verifyController->indexAction(
            $this->container->get(WorkerService::class),
            $this->container->get(VerifyRequestFactory::class)
        );
    }

    /**
     * @dataProvider indexActionDataProvider
     *
     * @param ParameterBag $postData
     * @param string $workerHostname
     * @param string $workerToken
     */
    public function testIndexAction(ParameterBag $postData, $workerHostname, $workerToken)
    {
        $workerActiveState = new State();
        $workerActiveState->setName(WorkerService::WORKER_ACTIVE_STATE);

        $worker = new ThisWorker();
        $worker->setHostname($workerHostname);
        $worker->setActivationToken($workerToken);
        $worker->setState($workerActiveState);

        $workerService = $this->container->get(WorkerService::class);
        $workerService->setGetResult($worker);

        $request = new Request();
        $request->request = $postData;
        $this->container->get('request_stack')->push($request);

        $response = $this->verifyController->indexAction(
            $workerService,
            $this->container->get(VerifyRequestFactory::class)
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function indexActionDataProvider()
    {
        return [
            'valid' => [
                'postData' => new ParameterBag([
                    VerifyRequestFactory::PARAMETER_HOSTNAME => 'foo',
                    VerifyRequestFactory::PARAMETER_TOKEN => 'bar',
                ]),
                'workerHostname' => 'foo',
                'workerToken' => 'bar',
            ],
        ];
    }
}
