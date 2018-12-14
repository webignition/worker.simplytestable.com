<?php

namespace App\Tests\Functional\Entity\Task;

use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\TaskTypeService;
use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

class TaskTest extends AbstractBaseTestCase
{
    public function testParentChildRelationship()
    {
        $entityManager = self::$container->get(EntityManagerInterface::class);

        /* @var TaskTypeService $taskTypeService */
        $taskTypeService = self::$container->get(TaskTypeService::class);

        $linkIntegrityTaskType = $taskTypeService->get(TypeInterface::TYPE_LINK_INTEGRITY);

        $parentTask = Task::create('http://example.com');
        $parentTask->setState(Task::STATE_IN_PROGRESS);
        $parentTask->setType($linkIntegrityTaskType);

        $childTask1 = Task::create('http://example.com/one');
        $childTask1->setState(Task::STATE_QUEUED);
        $childTask1->setType($linkIntegrityTaskType);
        $childTask1->setParentTask($parentTask);

        $childTask2 = Task::create('http://example.com/two');
        $childTask2->setState(Task::STATE_QUEUED);
        $childTask2->setType($linkIntegrityTaskType);
        $childTask2->setParentTask($parentTask);

        try {
            $entityManager->persist($parentTask);
            $entityManager->persist($childTask1);
            $entityManager->persist($childTask2);
            $entityManager->flush();
        } catch (ORMException $exception) {
        }

        $this->assertNotNull($parentTask->getId());
        $this->assertNotNull($childTask1->getId());
        $this->assertNotNull($childTask2->getId());

        $this->assertNull($parentTask->getParentTask());
        $this->assertEquals($parentTask, $childTask1->getParentTask());
        $this->assertEquals($parentTask, $childTask2->getParentTask());
    }
}
