<?php

namespace Tests\WorkerBundle\Functional\Guzzle;

use GuzzleHttp\Client;
use SimplyTestable\WorkerBundle\Services\HttpClientFactory;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;

class HttpClientFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->httpClientFactory = $this->container->get(HttpClientFactory::class);
    }

    public function testCreate()
    {
        $httpClient = $this->httpClientFactory->create();

        $this->assertInstanceOf(Client::class, $httpClient);
    }
}
