<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class TasksController extends Controller
{
    /**
     * @param QueueService $resqueQueueService
     * @param TasksService $tasksService
     *
     * @return Response
     * @throws \Exception
     */
    public function notifyAction(
        QueueService $resqueQueueService,
        TasksService $tasksService
    ) {
        if ($resqueQueueService->isEmpty('tasks-request')) {
            $resqueQueueService->enqueue(new TasksRequestJob(['limit' => $tasksService->getWorkerProcessCount()]));
        }

        return new Response();
    }
}
