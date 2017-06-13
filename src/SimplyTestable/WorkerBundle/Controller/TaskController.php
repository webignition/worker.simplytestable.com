<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Request\Task\CreateRequest;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TaskController extends BaseController
{
    public function __construct() {
//        $this->setInputDefinitions(array(
//            'createAction' => new InputDefinition(array(
//                new InputArgument('type', InputArgument::REQUIRED, 'Name of task type, case insensitive'),
//                new InputArgument('url', InputArgument::REQUIRED, 'URL of web page against which the task is to be performed')
//            )),
//            'createCollectionAction' => new InputDefinition(array(
//                new InputArgument('tasks', InputArgument::REQUIRED, 'Collection of task urls and test types')
//            )),
//            'cancelAction' => new InputDefinition(array(
//                new InputArgument('id', InputArgument::REQUIRED, 'ID of task to be cancelled')
//            )),
//            'cancelCollectionAction' => new InputDefinition(array(
//                new InputArgument('ids', InputArgument::REQUIRED, 'IDs of tasks to be cancelled')
//            ))
//
//        ));
//
//        $this->setRequestTypes(array(
//            'createAction' => 'POST',
//            'createCollectionAction' => 'POST',
//            'cancelAction' => 'POST',
//            'cancelCollectionAction' => 'POST'
//        ));
    }

    public function createAction()
    {
        if ($this->isInMaintenanceReadOnlyMode()) {
            return $this->sendServiceUnavailableResponse();
        }

        $createRequest = $this->container->get('simplytestable.services.request.factory.task.create')->create();

        if (!$createRequest->isValid()) {
            throw new BadRequestHttpException();
        }

        $task = $this->createTaskFromCreateRequest($createRequest);
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();

        $this->enqueueTaskPerformJob($task);

        return $this->sendResponse($task);
    }

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

        $task = $this->getTaskService()->getById($this->getArguments('cancelAction')->get('id'));
        if (is_null($task)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }

        $this->getTaskService()->cancel($task);

        $this->getTaskService()->getEntityManager()->remove($task);
        $this->getTaskService()->getEntityManager()->flush();

        return $this->sendSuccessResponse();
    }

    public function cancelCollectionAction()
    {
        if ($this->isInMaintenanceReadOnlyMode()) {
            return $this->sendServiceUnavailableResponse();
        }

        $taskIds = explode(',', $this->getArguments('cancelCollectionAction')->get('ids'));

        $cancelledTaskCount = 0;
        foreach ($taskIds as $taskId) {
            $task = $this->getTaskService()->getById($taskId);
            if (!is_null($task)) {
                $this->getTaskService()->cancel($task);
                $this->getTaskService()->getEntityManager()->remove($task);
                $cancelledTaskCount++;
            }
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
        $jobFactoryService = $this->get('simplytestable.services.resque.jobFactoryService');

        $resqueQueueService->enqueue(
            $jobFactoryService->create(
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
