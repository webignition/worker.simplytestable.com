<?php

namespace SimplyTestable\WorkerBundle\Controller;

use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class TasksController extends Controller
{
    /**
     * @param QueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param TasksService $tasksService
     *
     * @return Response
     * @throws \Exception
     */
    public function notifyAction(
        QueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        TasksService $tasksService
    ) {
        if ($resqueQueueService->isEmpty('tasks-request')) {
            $resqueQueueService->enqueue(
                $resqueJobFactory->create(
                    'tasks-request',
                    ['limit' => $tasksService->getWorkerProcessCount()]
                )
            );
        }

        return new Response();
    }
}
