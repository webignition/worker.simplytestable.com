<?php

namespace SimplyTestable\WorkerBundle\Tests;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Console\Tester\CommandTester;

abstract class BaseTestCase extends WebTestCase {

    const FIXTURES_DATA_RELATIVE_PATH = '/Fixtures/Data';

    /**
     *
     * @var Symfony\Bundle\FrameworkBundle\Client
     */
    protected $client;

    /**
     *
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;


    /**
     *
     * @var Symfony\Bundle\FrameworkBundle\Console\Application
     */
    private $application;


    protected function setUp() {
        $this->client = static::createClient();
        $this->container = $this->client->getKernel()->getContainer();
        $this->application = new Application(self::$kernel);
        $this->application->setAutoExit(false);

        foreach ($this->getCommands() as $command) {
            $this->application->add($command);
        }

        $this->setDefaultSystemState();
    }

    /**
     * @return array
     */
    protected static function getMockServices()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected static function createClient(array $options = array(), array $server = array())
    {
        $client = parent::createClient($options, $server);

        foreach (static::getMockServices() as $serviceId => $serviceClass) {
            $client->getContainer()->set($serviceId, \Mockery::mock($serviceClass));
        }

        return $client;
    }

    /**
     *
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getCommands() {
        return array_merge(array(
            new \Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand(),
            new \SimplyTestable\WorkerBundle\Command\Maintenance\DisableReadOnlyCommand(),
        ), $this->getAdditionalCommands());
    }

    /**
     *
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {
        return array();
    }

    protected function executeCommand($name, $arguments = array()) {
        $command = $this->application->find($name);
        $commandTester = new CommandTester($command);

        $arguments['command'] = $command->getName();

        return $commandTester->execute($arguments);
    }

    protected function setDefaultSystemState() {
        $this->executeCommand('simplytestable:maintenance:disable-read-only');
    }

    protected static function setupDatabaseIfNotExists() {
        if (self::areDatabaseMigrationsNeeded()) {
            self::setupDatabase();
        }
    }

    private static function areDatabaseMigrationsNeeded() {
        $migrationStatusOutputLines = array();
        exec('php app/console doctrine:migrations:status', $migrationStatusOutputLines);

        foreach ($migrationStatusOutputLines as $migrationStatusOutputLine) {
            if (substr_count($migrationStatusOutputLine, '>> New Migrations:')) {
                //var_dump($migrationStatusOutputLine, (int)trim(str_replace('>> Available Migrations:', '', $migrationStatusOutputLine)));
                if ((int)trim(str_replace('>> New Migrations:', '', $migrationStatusOutputLine)) > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function clearRedis() {
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
    protected function createController($controllerClass, $controllerMethod, array $postData = array(), array $queryData = array()) {
        $request = $this->createWebRequest();
        $request->attributes->set('_controller', $controllerClass.'::'.$controllerMethod);
        $request->request->add($postData);
        $request->query->add($queryData);
        $this->container->set('request', $request);

        $controllerCallable = $this->getControllerCallable($request);
        $controllerCallable[0]->setContainer($this->container);

        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('kernel.controller', new \Symfony\Component\HttpKernel\Event\FilterControllerEvent(
                self::$kernel,
                $controllerCallable,
                $request,
                HttpKernelInterface::MASTER_REQUEST
        ));

        return $controllerCallable[0];
    }

    private function getControllerCallable(Request $request) {
        $controllerResolver = new \Symfony\Component\HttpKernel\Controller\ControllerResolver();
        return $controllerResolver->getController($request);
    }

    /**
     * Creates a new Request object and hydrates it with the proper values to make
     * a valid web request.
     *
     * @return \Symfony\Component\HttpFoundation\Request The hydrated Request object.
     */
    protected function createWebRequest() {
        $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        return $request;
    }


    /**
     *
     * @param string $testName
     * @return string
     */
    protected function getFixturesDataPath($testName = null, $upBaseLevels = 0) {
        $path = __DIR__ . self::FIXTURES_DATA_RELATIVE_PATH . '/' . str_replace('\\', DIRECTORY_SEPARATOR, get_class($this));

        if ($upBaseLevels > 0) {
            $pathParts = explode('/', $path);

            for ($count = 0; $count < $upBaseLevels; $count++) {
                array_pop($pathParts);
            }

            $path = implode('/', $pathParts);
        }

        if (!is_null($testName)) {
            $path .=  '/' . $testName;
        }

        return $path;
    }

    protected function tearDown() {
        $this->container->get('doctrine')->getConnection()->close();
        parent::tearDown();
    }

}
