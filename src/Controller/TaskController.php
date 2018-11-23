<?php

namespace App\Controller;

use App\Resque\Job\TaskPrepareJob;
use Doctrine\ORM\EntityManagerInterface;
use App\Model\TaskCollection;
use App\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use App\Services\Request\Factory\Task\CancelRequestFactory;
use App\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use App\Services\Resque\QueueService;
use App\Services\TaskFactory;
use App\Services\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     * @param QueueService $resqueQueueService
     *
     * @return JsonResponse
     */
    public function createCollectionAction(
        CreateRequestCollectionFactory $createRequestCollectionFactory,
        TaskFactory $taskFactory,
        QueueService $resqueQueueService
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
            $resqueQueueService->enqueue(new TaskPrepareJob(['id' => $task->getId()]));
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

        if (!$cancelRequest->isValid()) {
            throw new BadRequestHttpException();
        }

        $this->taskService->cancel($cancelRequest->getTask());
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
            $this->taskService->cancel($cancelRequest->getTask());
            $this->entityManager->remove($cancelRequest->getTask());
            $cancelledTaskCount++;
        }

        if ($cancelledTaskCount > 0) {
            $this->entityManager->flush();
        }

        return new JsonResponse();
    }
}
