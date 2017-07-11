<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Controller;

use SimplyTestable\WorkerBundle\Controller\VerifyController;
use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Services\Request\Factory\VerifyRequestFactory;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VerifyControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function getServicesToMock()
    {
        return [
            'simplytestable.services.workerservice',
        ];
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
        $this->mockWorkerService($workerHostname, $workerToken);

        $request = new Request();
        $request->request = $postData;
        $this->container->get('request_stack')->push($request);

        $response = $this->createVerifyController()->indexAction();
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

    /**
     * @return VerifyController
     */
    private function createVerifyController()
    {
        $controller = new VerifyController();
        $controller->setContainer($this->container);

        return $controller;
    }

    /**
     * @param string $hostname
     * @param string $token
     */
    private function mockWorkerService($hostname, $token)
    {
        $worker = new ThisWorker();
        $worker->setHostname($hostname);
        $worker->setActivationToken($token);

        $this->container->get('simplytestable.services.workerservice')
            ->shouldReceive('get')
            ->andReturn($worker);
    }
}
