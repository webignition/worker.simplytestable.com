<?php

namespace App\Tests\Functional\Controller;

use App\Controller\VerifyController;
use App\Entity\ThisWorker;
use App\Services\Request\Factory\VerifyRequestFactory;
use App\Services\WorkerService;

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
     * @var string
     */
    private $indexActionUrl;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->verifyController = new VerifyController();
        $this->indexActionUrl = $this->router->generate('verify');
    }

    public function testIndexActionInvalidRequestHostname()
    {
        $this->client->request('POST', $this->indexActionUrl, [
            VerifyRequestFactory::PARAMETER_HOSTNAME => 'invalid-hostname',
            VerifyRequestFactory::PARAMETER_TOKEN => self::$container->getParameter('token'),
        ]);

        $response = $this->client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testIndexActionInvalidRequestToken()
    {
        $this->client->request('POST', $this->indexActionUrl, [
            VerifyRequestFactory::PARAMETER_HOSTNAME => self::$container->getParameter('hostname'),
            VerifyRequestFactory::PARAMETER_TOKEN => 'invalid-token',
        ]);

        $response = $this->client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @dataProvider indexActionDataProvider
     *
     * @param array $postData
     */
    public function testIndexActionInvalidRequest(array $postData)
    {
        $this->client->request('POST', $this->indexActionUrl, $postData);

        $response = $this->client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function indexActionDataProvider(): array
    {
        return [
            'empty request' => [
                'postData' => [],
            ],
            'hostname missing' => [
                'postData' => [
                    VerifyRequestFactory::PARAMETER_TOKEN => 'token',
                ],
            ],
            'token missing' => [
                'postData' => [
                    VerifyRequestFactory::PARAMETER_HOSTNAME => 'hostname',
                ],
            ],
        ];
    }
}
