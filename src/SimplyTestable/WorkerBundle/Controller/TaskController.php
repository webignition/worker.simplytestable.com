<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Request\Task\CreateRequest;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TaskController extends BaseController
{
    public function createCollectionAction()
    {
        if ($this->isInMaintenanceReadOnlyMode()) {
            return $this->sendServiceUnavailableResponse();
        }

        $createCollectionRequest =
            $this->container->get('simplytestable.services.request.factory.task.createcollection')->create();

        $tasks = [];

        foreach ($createCollectionRequest->getCreateRequests() as $createRequest) {
            $task = $this->createTaskFromCreateRequest($createRequest);
            $tasks[] = $task;

            $this->getTaskService()->getEntityManager()->persist($task);
        }

        $this->getTaskService()->getEntityManager()->flush();

        foreach ($tasks as $task) {
            $this->enqueueTaskPerformJob($task);
        }

        return $this->sendResponse($tasks);
    }

    public function cancelAction()
    {
        if ($this->isInMaintenanceReadOnlyMode()) {
            return $this->sendServiceUnavailableResponse();
        }

        $cancelRequest = $this->container->get('simplytestable.services.request.factory.task.cancel')->create();

        if (!$cancelRequest->isValid()) {
            throw new BadRequestHttpException();
        }

        $this->getTaskService()->cancel($cancelRequest->getTask());
        $this->getTaskService()->getEntityManager()->remove($cancelRequest->getTask());
        $this->getTaskService()->getEntityManager()->flush();

        return $this->sendSuccessResponse();
    }

    public function cancelCollectionAction()
    {
        if ($this->isInMaintenanceReadOnlyMode()) {
            return $this->sendServiceUnavailableResponse();
        }

        $cancelCollectionRequest =
            $this->container->get('simplytestable.services.request.factory.task.cancelcollection')->create();

        $cancelledTaskCount = 0;
        foreach ($cancelCollectionRequest->getCancelRequests() as $cancelRequest) {
            $this->getTaskService()->cancel($cancelRequest->getTask());
            $this->getTaskService()->getEntityManager()->remove($cancelRequest->getTask());
            $cancelledTaskCount++;
        }

        if ($cancelledTaskCount > 0) {
            $this->getTaskService()->getEntityManager()->flush();
        }

        return $this->sendSuccessResponse();
    }

    /**
     * @param CreateRequest $createRequest
     *
     * @return Task
     */
    private function createTaskFromCreateRequest(CreateRequest $createRequest)
    {
        return $this->getTaskService()->create(
            $createRequest->getUrl(),
            $createRequest->getTaskType(),
            $createRequest->getParameters()
        );
    }

    /**
     * @param Task $task
     */
    private function enqueueTaskPerformJob(Task $task)
    {
        $resqueQueueService = $this->get('simplytestable.services.resque.queueService');
        $resqueJobFactory = $this->get('simplytestable.services.resque.jobfactory');

        $resqueQueueService->enqueue(
            $resqueJobFactory->create(
                'task-perform',
                ['id' => $task->getId()]
            )
        );
    }

    /**
     * @return TaskService
     */
    private function getTaskService()
    {
        return $this->container->get('simplytestable.services.taskservice');
    }
}
