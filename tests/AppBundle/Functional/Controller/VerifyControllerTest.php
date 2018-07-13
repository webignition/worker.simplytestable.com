<?php

namespace Tests\AppBundle\Functional\Controller;

use AppBundle\Controller\VerifyController;
use AppBundle\Entity\State;
use AppBundle\Entity\ThisWorker;
use AppBundle\Services\Request\Factory\VerifyRequestFactory;
use AppBundle\Services\WorkerService;

/**
 * @group Controller/VerifyController
 */
class VerifyControllerTest extends AbstractControllerTest
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

    /**
     * @dataProvider indexActionDataProvider
     *
     * @param array $postData
     * @param string $workerHostname
     * @param string $workerToken
     */
    public function testIndexAction(array $postData, $workerHostname, $workerToken)
    {
        $workerActiveState = new State();
        $workerActiveState->setName(WorkerService::WORKER_ACTIVE_STATE);

        $worker = new ThisWorker();
        $worker->setHostname($workerHostname);
        $worker->setActivationToken($workerToken);
        $worker->setState($workerActiveState);

        $workerService = self::$container->get(WorkerService::class);
        $workerService->setGetResult($worker);

        $this->client->request('POST', $this->router->generate('verify'), $postData);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function indexActionDataProvider()
    {
        return [
            'valid' => [
                'postData' => [
                    VerifyRequestFactory::PARAMETER_HOSTNAME => 'foo',
                    VerifyRequestFactory::PARAMETER_TOKEN => 'bar',
                ],
                'workerHostname' => 'foo',
                'workerToken' => 'bar',
            ],
        ];
    }
}
