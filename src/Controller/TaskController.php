<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Model\TaskCollection;
use App\Resque\Job\TaskPerformJob;
use App\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use App\Services\Request\Factory\Task\CancelRequestFactory;
use App\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use App\Services\Resque\QueueService;
use App\Services\TaskFactory;
use App\Services\TaskService;
use App\Services\WorkerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TaskController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WorkerService
     */
    private $workerService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @param EntityManagerInterface $entityManager
     * @param WorkerService $workerService
     * @param TaskService $taskService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        WorkerService $workerService,
        TaskService $taskService
    ) {
        $this->entityManager = $entityManager;
        $this->workerService = $workerService;
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
        if ($this->workerService->isMaintenanceReadOnly()) {
            throw new ServiceUnavailableHttpException();
        }

        $createCollectionRequest = $createRequestCollectionFactory->create();

        $tasks = new TaskCollection();

        foreach ($createCollectionRequest->getCreateRequests() as $createRequest) {
            $task = $taskFactory->createFromRequest($createRequest);
            $tasks->add($task);

            $this->entityManager->persist($task);
        }

        $this->entityManager->flush();

        foreach ($tasks as $task) {
            $resqueQueueService->enqueue(new TaskPerformJob(['id' => $task->getId()]));
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
        if ($this->workerService->isMaintenanceReadOnly()) {
            throw new ServiceUnavailableHttpException();
        }

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
        if ($this->workerService->isMaintenanceReadOnly()) {
            throw new ServiceUnavailableHttpException();
        }

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
