<?php

namespace SimplyTestable\WorkerBundle\Tests;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\CoreApplicationService;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\MemcacheService;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactoryService;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use Doctrine\Common\Cache\MemcacheCache;
use GuzzleHttp\Subscriber\Mock as HttpMockSubscriber;
use webignition\WebResource\Service\Service as WebResourceService;

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

    protected function getTaskFactory()
    {
        if (is_null($this->taskFactory)) {
            $this->taskFactory = new TaskFactory(
                $this->getTaskService(),
                $this->getTaskTypeService(),
                $this->getStateService(),
                $this->getEntityManager()
            );
        }

        return $this->taskFactory;
    }

    /**
     * @return StateService
     */
    protected function getStateService()
    {
        return $this->container->get('simplytestable.services.stateservice');
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
     * @return JobFactoryService
     */
    protected function getResqueJobFactoryService()
    {
        return $this->container->get('simplytestable.services.resque.jobFactoryService');
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
     * @return TasksService
     */
    protected function getTasksService()
    {
        return $this->container->get('simplytestable.services.tasksservice');
    }

    /**
     * @return CoreApplicationService
     */
    protected function getCoreApplicationService()
    {
        return $this->container->get('simplytestable.services.coreapplicationservice');
    }

    /**
     * @return MemcacheService
     */
    protected function getMemcacheService()
    {
        return $this->container->get('simplytestable.services.memcacheservice');
    }

    /**
     * @return WebResourceService
     */
    protected function getWebResourceService()
    {
        return $this->container->get('simplytestable.services.webresourceservice');
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
        $memcacheCache = new MemcacheCache();
        $memcacheCache->setMemcache($this->getMemcacheService()->get());
        $memcacheCache->deleteAll();

        $this->getHttpClientService()->get()->getEmitter()->attach(
            new HttpMockSubscriber($fixtures)
        );
    }
}
