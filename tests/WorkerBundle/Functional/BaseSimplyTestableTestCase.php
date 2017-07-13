<?php

namespace Tests\WorkerBundle\Functional;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\HttpCache;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\MemcachedService;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Factory\TaskFactory;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;

abstract class BaseSimplyTestableTestCase extends BaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->container->get('SimplyTestable\WorkerBundle\Services\WorkerService')->setActive();
    }

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    /**
     * @return TaskFactory
     */
    protected function getTaskFactory()
    {
        $stateService = $this->container->get(StateService::class);
        $taskService = $this->container->get(TaskService::class);
        $taskTypeService = $this->container->get(TaskTypeService::class);

        if (is_null($this->taskFactory)) {
            $this->taskFactory = new TaskFactory(
                $taskService,
                $taskTypeService,
                $stateService,
                $this->getEntityManager()
            );
        }

        return $this->taskFactory;
    }

//    /**
//     * @return WorkerService
//     */
//    protected function getWorkerService()
//    {
//        return $this->container->get('simplytestable.services.workerservice');
//    }
//
//    /**
//     * @return QueueService
//     */
//    protected function getResqueQueueService()
//    {
//        return $this->container->get('simplytestable.services.resque.queueservice');
//    }
//
//    /**
//     * @return ResqueJobFactory
//     */
//    protected function getResqueJobFactory()
//    {
//        return $this->container->get('simplytestable.services.resque.jobfactory');
//    }
//
//    /**
//     * @return TaskService
//     */
//    protected function getTaskService()
//    {
//        return $this->container->get('simplytestable.services.taskservice');
//    }
//
//    /**
//     * @return TaskTypeService
//     */
//    protected function getTaskTypeService()
//    {
//        return $this->container->get('simplytestable.services.tasktypeservice');
//    }
//
//    /**
//     * @return MemcachedService
//     */
//    protected function getMemcachedService()
//    {
//        return $this->container->get('simplytestable.services.memcachedservice');
//    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->container->get('doctrine')->getManager();
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
        $entities = $this->getEntityManager()->getRepository($entityName)->findAll();
        if (is_array($entities) && count($entities) > 0) {
            foreach ($entities as $entity) {
                $this->getEntityManager()->remove($entity);
            }

            $this->getEntityManager()->flush();
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
