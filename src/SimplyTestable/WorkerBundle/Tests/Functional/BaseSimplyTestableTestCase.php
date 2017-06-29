<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\MemcachedService;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;

abstract class BaseSimplyTestableTestCase extends BaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->getWorkerService()->setActive();
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
        $stateService = $this->container->get('simplytestable.services.stateservice');

        if (is_null($this->taskFactory)) {
            $this->taskFactory = new TaskFactory(
                $this->getTaskService(),
                $this->getTaskTypeService(),
                $stateService,
                $this->getEntityManager()
            );
        }

        return $this->taskFactory;
    }

    /**
     * @return WorkerService
     */
    protected function getWorkerService()
    {
        return $this->container->get('simplytestable.services.workerservice');
    }

    /**
     * @return QueueService
     */
    protected function getResqueQueueService()
    {
        return $this->container->get('simplytestable.services.resque.queueservice');
    }

    /**
     * @return ResqueJobFactory
     */
    protected function getResqueJobFactory()
    {
        return $this->container->get('simplytestable.services.resque.jobfactory');
    }

    /**
     * @return HttpClientService
     */
    protected function getHttpClientService()
    {
        return $this->container->get('simplytestable.services.httpclientservice');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->container->get('simplytestable.services.taskservice');
    }

    /**
     * @return TaskTypeService
     */
    protected function getTaskTypeService()
    {
        return $this->container->get('simplytestable.services.tasktypeservice');
    }

    /**
     * @return MemcachedService
     */
    protected function getMemcachedService()
    {
        return $this->container->get('simplytestable.services.memcachedservice');
    }

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
        $this->container->get('simplytestable.services.httpcache')->clear();

        $this->getHttpClientService()->get()->getEmitter()->attach(
            new HttpMockSubscriber($fixtures)
        );
    }
}
