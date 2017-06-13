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
     *
     * Builds a Controller object and the request to satisfy it. Attaches the request
     * to the object and to the container.
     *
     * @param string $controllerClass The full path to the controller class
     * @param string $controllerMethod Name of the controller method to be called
     * @param array $postData Array of post values
     * @param array $queryData Array of query string values
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Controller
     */
    protected function createController(
        $controllerClass,
        $controllerMethod,
        array $postData = [],
        array $queryData = []
    ) {
        $request = $this->createWebRequest();
        $request->attributes->set('_controller', $controllerClass.'::'.$controllerMethod);
        $request->request->add($postData);
        $request->query->add($queryData);
        $this->container->set('request', $request);

        $controllerCallable = $this->getControllerCallable($request);
        $controllerCallable[0]->setContainer($this->container);

        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('kernel.controller', new FilterControllerEvent(
                self::$kernel,
                $controllerCallable,
                $request,
                HttpKernelInterface::MASTER_REQUEST
        ));

        return $controllerCallable[0];
    }

    /**
     * @param Request $request
     *
     * @return bool|mixed
     */
    private function getControllerCallable(Request $request)
    {
        $controllerResolver = new ControllerResolver();

        return $controllerResolver->getController($request);
    }

    /**
     * Creates a new Request object and hydrates it with the proper values to make
     * a valid web request.
     *
     * @return \Symfony\Component\HttpFoundation\Request The hydrated Request object.
     */
    protected function createWebRequest()
    {
        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        return $request;
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
