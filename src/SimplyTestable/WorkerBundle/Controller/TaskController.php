<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Model\TaskCollection;
use SimplyTestable\WorkerBundle\Request\Task\CreateRequest;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TaskController extends Controller
{
    /**
     * @return Response|JsonResponse
     */
    public function createCollectionAction()
    {
        if ($this->container->get('simplytestable.services.workerservice')->isMaintenanceReadOnly()) {
            throw new ServiceUnavailableHttpException();
        }

        $createCollectionRequest =
            $this->container->get('simplytestable.services.request.factory.task.createcollection')->create();

        $tasks = new TaskCollection();

        foreach ($createCollectionRequest->getCreateRequests() as $createRequest) {
            $task = $this->createTaskFromCreateRequest($createRequest);
            $tasks->add($task);

            $this->getTaskService()->getEntityManager()->persist($task);
        }

        $this->getTaskService()->getEntityManager()->flush();

        foreach ($tasks as $task) {
            $this->enqueueTaskPerformJob($task);
        }

        return new JsonResponse($tasks->jsonSerialize());
    }

    public function cancelAction()
    {
        if ($this->container->get('simplytestable.services.workerservice')->isMaintenanceReadOnly()) {
            throw new ServiceUnavailableHttpException();
        }

        $cancelRequest = $this->container->get('simplytestable.services.request.factory.task.cancel')->create();

        if (!$cancelRequest->isValid()) {
            throw new BadRequestHttpException();
        }

        $this->getTaskService()->cancel($cancelRequest->getTask());
        $this->getTaskService()->getEntityManager()->remove($cancelRequest->getTask());
        $this->getTaskService()->getEntityManager()->flush();

        return new Response();
    }

    public function cancelCollectionAction()
    {
        if ($this->container->get('simplytestable.services.workerservice')->isMaintenanceReadOnly()) {
            throw new ServiceUnavailableHttpException();
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

        return new Response();
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
