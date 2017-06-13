<?php

namespace SimplyTestable\WorkerBundle\Tests;

use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use SimplyTestable\WorkerBundle\Command\Maintenance\DisableReadOnlyCommand;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class BaseTestCase extends WebTestCase
{
    const FIXTURES_DATA_RELATIVE_PATH = '/Fixtures/Data';

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

        foreach ($this->getCommands() as $command) {
            $this->application->add($command);
        }
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

    /**
     * @return ContainerAwareCommand[]
     */
    protected function getCommands()
    {
        return array_merge([
            new LoadDataFixturesDoctrineCommand(),
            new DisableReadOnlyCommand(),
        ], $this->getAdditionalCommands());
    }

    /**
     * @return ContainerAwareCommand[]
     */
    protected function getAdditionalCommands()
    {
        return [];
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
        $this->container->get('doctrine')->getConnection()->close();
        parent::tearDown();
    }
}
