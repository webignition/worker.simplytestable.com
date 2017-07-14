<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Model\TaskCollection;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TaskFactory;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TaskController extends AbstractController
{
    /**
     * @var WorkerService
     */
    private $workerService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @param WorkerService $workerService
     * @param TaskService $taskService
     */
    public function __construct(WorkerService $workerService, TaskService $taskService)
    {
        $this->workerService = $workerService;
        $this->taskService = $taskService;
    }

    /**
     * @param CreateRequestCollectionFactory $createRequestCollectionFactory
     * @param TaskFactory $taskFactory
     * @param QueueService $resqueQueueService
     * @param JobFactory $resqueJobFactory
     *
     * @return JsonResponse|Response
     */
    public function createCollectionAction(
        CreateRequestCollectionFactory $createRequestCollectionFactory,
        TaskFactory $taskFactory,
        QueueService $resqueQueueService,
        JobFactory $resqueJobFactory
    ) {
        if ($this->workerService->isMaintenanceReadOnly()) {
            throw new ServiceUnavailableHttpException();
        }

        $createCollectionRequest = $createRequestCollectionFactory->create();

        $tasks = new TaskCollection();

        foreach ($createCollectionRequest->getCreateRequests() as $createRequest) {
            $task = $taskFactory->createFromRequest($createRequest);
            $tasks->add($task);

            $this->taskService->getEntityManager()->persist($task);
        }

        $this->taskService->getEntityManager()->flush();

        foreach ($tasks as $task) {
            $resqueQueueService->enqueue(
                $resqueJobFactory->create(
                    'task-perform',
                    ['id' => $task->getId()]
                )
            );
        }

        return new JsonResponse($tasks->jsonSerialize());
    }

    /**
     * @param CancelRequestFactory $cancelRequestFactory
     *
     * @return Response
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
        $this->taskService->getEntityManager()->remove($cancelRequest->getTask());
        $this->taskService->getEntityManager()->flush();

        return new Response();
    }

    /**
     * @param CancelRequestCollectionFactory $cancelRequestCollectionFactory
     *
     * @return Response
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
            $this->taskService->getEntityManager()->remove($cancelRequest->getTask());
            $cancelledTaskCount++;
        }

        if ($cancelledTaskCount > 0) {
            $this->taskService->getEntityManager()->flush();
        }

        return new Response();
    }
}
