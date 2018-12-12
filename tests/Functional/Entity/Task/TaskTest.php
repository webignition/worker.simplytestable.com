<?php

namespace App\Tests\Functional\Entity\Task;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
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

        $parentTask = $this->taskService->create('http://example.com', $linkIntegrityTaskType, '');
        $parentTask->setState(Task::STATE_IN_PROGRESS);

        $childTask1 = $this->taskService->create('http://example.com/one', $linkIntegrityTaskType, '');
        $childTask1->setState(Task::STATE_QUEUED);
        $childTask1->setParentTask($parentTask);

        $childTask2 = $this->taskService->create('http://example.com/two', $linkIntegrityTaskType, '');
        $childTask2->setState(Task::STATE_QUEUED);
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
        $task = $this->taskService->create(
            'http://example.com/',
            $this->taskTypeService->get(TypeInterface::TYPE_HTML_VALIDATION),
            ''
        );

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->assertNotNull($task->getId());

        $htmlResource = new CachedResource();
        $htmlResource->setUrl('http://example.com/');
        $htmlResource->setBody('<doctype html><html lang="en"></html>');

        $cssResource = new CachedResource();
        $cssResource->setUrl('http://example.com/style.css');
        $cssResource->setBody('css');

        $this->entityManager->persist($htmlResource);
        $this->entityManager->persist($cssResource);
        $this->entityManager->flush();

        $this->assertNotNull($htmlResource->getId());
        $this->assertNotNull($cssResource->getId());

        $task->addResource($htmlResource);
        $task->addResource($cssResource);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->assertEquals(
            [
                $htmlResource->getId() => $htmlResource->getUrl(),
                $cssResource->getId() => $cssResource->getUrl(),
            ],
            $task->getResourceIndex()
        );

        $taskId = $task->getId();

        $this->entityManager->clear();

        $retievedTask = $this->entityManager->find(Task::class, $taskId);

        $this->assertEquals(
            [
                $htmlResource->getId() => $htmlResource->getUrl(),
                $cssResource->getId() => $cssResource->getUrl(),
            ],
            $retievedTask->getResourceIndex()
        );
    }
}
