<?php

namespace App\Controller;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Request\Task\CancelRequest;
use Doctrine\ORM\EntityManagerInterface;
use App\Model\TaskCollection;
use App\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use App\Services\Request\Factory\Task\CancelRequestFactory;
use App\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use App\Services\TaskFactory;
use App\Services\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TaskController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskService
     */
    private $taskService;

    public function __construct(EntityManagerInterface $entityManager, TaskService $taskService)
    {
        $this->entityManager = $entityManager;
        $this->taskService = $taskService;
    }

    /**
     * @param CreateRequestCollectionFactory $createRequestCollectionFactory
     * @param TaskFactory $taskFactory
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function createCollectionAction(
        CreateRequestCollectionFactory $createRequestCollectionFactory,
        TaskFactory $taskFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $createCollectionRequest = $createRequestCollectionFactory->create();

        $tasks = new TaskCollection();

        foreach ($createCollectionRequest->getCreateRequests() as $createRequest) {
            $task = $taskFactory->createFromRequest($createRequest);
            $tasks->add($task);

            $this->entityManager->persist($task);
        }

        $this->entityManager->flush();

        foreach ($tasks as $task) {
            $eventDispatcher->dispatch(TaskEvent::TYPE_CREATED, new TaskEvent($task));
        }

        return new JsonResponse($tasks->jsonSerialize());
    }

    /**
     * @param CancelRequestFactory $cancelRequestFactory
     *
     * @return JsonResponse
     */
    public function cancelAction(CancelRequestFactory $cancelRequestFactory)
    {
        $cancelRequest = $cancelRequestFactory->create();

        if (!$cancelRequest instanceof CancelRequest) {
            throw new BadRequestHttpException();
        }

        $task = $cancelRequest->getTask();
        $task->setState(Task::STATE_CANCELLED);

        $this->entityManager->remove($cancelRequest->getTask());
        $this->entityManager->flush();

        return new JsonResponse();
    }

    /**
     * @param CancelRequestCollectionFactory $cancelRequestCollectionFactory
     *
     * @return JsonResponse
     */
    public function cancelCollectionAction(CancelRequestCollectionFactory $cancelRequestCollectionFactory)
    {
        $cancelCollectionRequest = $cancelRequestCollectionFactory->create();

        $cancelledTaskCount = 0;
        foreach ($cancelCollectionRequest->getCancelRequests() as $cancelRequest) {
            $task = $cancelRequest->getTask();
            $task->setState(Task::STATE_CANCELLED);

            $this->entityManager->remove($cancelRequest->getTask());
            $cancelledTaskCount++;
        }

        if ($cancelledTaskCount > 0) {
            $this->entityManager->flush();
        }

        return new JsonResponse();
    }
}
