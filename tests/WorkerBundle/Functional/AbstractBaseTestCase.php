<?php

namespace Tests\WorkerBundle\Functional;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\HttpCache;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tests\WorkerBundle\Factory\TestTaskFactory;
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

    protected function removeAllTasks()
    {
        $this->removeAllForEntity(Task::class);
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
     * @param string $entityName
     */
    private function removeAllForEntity($entityName)
    {
        $entityManager = $this->container->get('doctrine')->getManager();

        $entities = $entityManager->getRepository($entityName)->findAll();
        if (is_array($entities) && count($entities) > 0) {
            foreach ($entities as $entity) {
                $entityManager->remove($entity);
            }

            $entityManager->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        \Mockery::close();

        $this->client = null;

        parent::tearDown();
    }
}
