<?php

namespace Tests\WorkerBundle\Functional;

use SimplyTestable\WorkerBundle\Services\HttpCache;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;

abstract class AbstractBaseTestCase extends WebTestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->client = static::createClient();
        $this->container = $this->client->getKernel()->getContainer();
        $this->container->get(WorkerService::class)->setActive();

        $this->container->get('doctrine')->getConnection()->beginTransaction();
    }

    protected function clearRedis()
    {
        $output = array();
        $returnValue = null;

        exec('redis-cli -r 1 flushall', $output, $returnValue);

        if ($output !== array('OK')) {
            return false;
        }

        return $returnValue === 0;
    }

    /**
     * @param array $fixtures
     */
    protected function setHttpFixtures($fixtures)
    {
        $httpCache = $this->container->get(HttpCache::class);
        $httpCache->clear();

        $httpClientService = $this->container->get(HttpClientService::class);
        $httpClientService->get()->getEmitter()->attach(
            new HttpMockSubscriber($fixtures)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        if (!is_null($this->container)) {
            $this->container->get('doctrine')->getConnection()->close();
        }

        \Mockery::close();

        $this->client = null;

        parent::tearDown();
    }
}
