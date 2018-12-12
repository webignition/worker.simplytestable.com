<?php

namespace App\Tests\Functional\Entity\Task;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\SourceFactory;
use App\Services\TaskService;
use App\Services\TaskTypeService;
use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\ORM\EntityManagerInterface;

class TaskTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->taskService = self::$container->get(TaskService::class);
        $this->taskTypeService = self::$container->get(TaskTypeService::class);
    }

    public function testParentChildRelationship()
    {
        $linkIntegrityTaskType = $this->taskTypeService->get(TypeInterface::TYPE_LINK_INTEGRITY);

        $parentTask = Task::create($linkIntegrityTaskType, 'http://example.com');
        $parentTask->setState(Task::STATE_IN_PROGRESS);

        $childTask1 = Task::create($linkIntegrityTaskType, 'http://example.com/one');
        $childTask1->setParentTask($parentTask);

        $childTask2 = Task::create($linkIntegrityTaskType, 'http://example.com/two');
        $childTask2->setParentTask($parentTask);

        $this->entityManager->persist($parentTask);
        $this->entityManager->persist($childTask1);
        $this->entityManager->persist($childTask2);
        $this->entityManager->flush();

        $this->assertNotNull($parentTask->getId());
        $this->assertNotNull($childTask1->getId());
        $this->assertNotNull($childTask2->getId());

        $this->assertNull($parentTask->getParentTask());
        $this->assertEquals($parentTask, $childTask1->getParentTask());
        $this->assertEquals($parentTask, $childTask2->getParentTask());
    }

    public function testResourceIndexPopulateAndRetrieve()
    {
        $htmlUrl = 'http://example.com';
        $httpUnavailableUrl = 'http://example.com/404';
        $curlUnavailableUrl = 'http://example.com/timeout';
        $unknownUnavailableUrl = 'http://example.com/unknown';

        $sourceFactory = self::$container->get(SourceFactory::class);

        $task = $this->taskService->create(
            'http://example.com/',
            $this->taskTypeService->get(TypeInterface::TYPE_HTML_VALIDATION),
            ''
        );

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->assertNotNull($task->getId());

        $htmlResource = new CachedResource();
        $htmlResource->setUrl($htmlUrl);
        $htmlResource->setBody('<doctype html><html lang="en"></html>');

        $this->entityManager->persist($htmlResource);
        $this->entityManager->flush();

        $this->assertNotNull($htmlResource->getId());

        $htmlSource = $sourceFactory->fromCachedResource($htmlResource);
        $httpUnavailableSource = $sourceFactory->createHttpFailedSource($httpUnavailableUrl, 404);
        $curlUnavailableResource = $sourceFactory->createCurlFailedSource($curlUnavailableUrl, 28);
        $unknownUnavailableResource = $sourceFactory->createUnknownFailedSource($unknownUnavailableUrl);

        $task->addSource($htmlSource);
        $task->addSource($httpUnavailableSource);
        $task->addSource($curlUnavailableResource);
        $task->addSource($unknownUnavailableResource);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $expectedTaskSources = [
            $htmlUrl => new Source($htmlUrl, Source::TYPE_CACHED_RESOURCE, $htmlResource->getId()),
            $httpUnavailableUrl => new Source($httpUnavailableUrl, Source::TYPE_UNAVAILABLE, 'http:404'),
            $curlUnavailableUrl => new Source($curlUnavailableUrl, Source::TYPE_UNAVAILABLE, 'curl:28'),
            $unknownUnavailableUrl => new Source($unknownUnavailableUrl, Source::TYPE_UNAVAILABLE, 'unknown:0'),
        ];

        $this->assertEquals($expectedTaskSources, $task->getSources());

        $taskId = $task->getId();

        $this->entityManager->clear();

        $retievedTask = $this->entityManager->find(Task::class, $taskId);

        $this->assertEquals($expectedTaskSources, $retievedTask->getSources());
    }
}
