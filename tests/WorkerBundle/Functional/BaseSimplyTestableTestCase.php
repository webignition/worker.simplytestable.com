<?php

namespace Tests\WorkerBundle\Functional;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\HttpCache;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;

abstract class BaseSimplyTestableTestCase extends BaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->container->get(WorkerService::class)->setActive();
    }

    /**
     * @var TestTaskFactory
     */
    private $taskFactory;

    /**
     * @return TestTaskFactory
     */
    protected function getTestTaskFactory()
    {
        if (is_null($this->taskFactory)) {
            /* @var EntityManagerInterface $entityManager */
            $entityManager = $this->container->get('doctrine')->getManager();

            $this->taskFactory = new TestTaskFactory(
                $this->container->get(TaskService::class),
                $this->container->get(TaskTypeService::class),
                $this->container->get(StateService::class),
                $entityManager
            );
        }

        return $this->taskFactory;
    }

    protected function removeAllTasks()
    {
        $this->removeAllForEntity(Task::class);
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
}
