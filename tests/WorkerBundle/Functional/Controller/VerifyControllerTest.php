<?php

namespace Tests\WorkerBundle\Functional\Controller;

use SimplyTestable\WorkerBundle\Controller\VerifyController;
use SimplyTestable\WorkerBundle\Entity\State;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Services\Request\Factory\VerifyRequestFactory;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class VerifyControllerTest extends BaseSimplyTestableTestCase
{
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

        $verifyController = new VerifyController();

        $response = $verifyController->indexAction(
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
