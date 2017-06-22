<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseTestCase extends WebTestCase
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
     * @var Application
     */
    protected $application;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->client = static::createClient();
        $this->container = $this->client->getKernel()->getContainer();
        $this->application = new Application(self::$kernel);
        $this->application->setAutoExit(false);
    }

    /**
     * @return string[]
     */
    protected static function getServicesToMock()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected static function createClient(array $options = array(), array $server = array())
    {
        $client = parent::createClient($options, $server);

        foreach (static::getServicesToMock() as $serviceId) {
            $mockedService = \Mockery::mock(
                $client->getContainer()->get($serviceId)
            );

            $client->getContainer()->set($serviceId, $mockedService);
        }

        return $client;
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
     * {@inheritdoc}
     */
    protected function tearDown()
    {
//        $this->container->get('doctrine')->getConnection()->close();
        parent::tearDown();
    }
}