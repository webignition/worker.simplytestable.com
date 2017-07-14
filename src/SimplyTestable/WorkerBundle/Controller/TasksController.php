<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Services\Resque\JobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class TasksController extends Controller
{
    /**
     * @param QueueService $resqueQueueService
     * @param JobFactory $resqueJobFactory
     * @param TasksService $tasksService
     *
     * @return Response
     */
    public function notifyAction(
        QueueService $resqueQueueService,
        JobFactory $resqueJobFactory,
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
