<?php

namespace AppBundle\Controller;

use AppBundle\Resque\Job\TasksRequestJob;
use AppBundle\Services\Resque\QueueService;
use AppBundle\Services\TasksService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class TasksController extends Controller
{
    /**
     * @param QueueService $resqueQueueService
     * @param TasksService $tasksService
     *
     * @return JsonResponse
     */
    public function notifyAction(
        QueueService $resqueQueueService,
        TasksService $tasksService
    ) {
        if ($resqueQueueService->isEmpty('tasks-request')) {
            $resqueQueueService->enqueue(new TasksRequestJob(['limit' => $tasksService->getWorkerProcessCount()]));
        }

        return new JsonResponse();
    }
}
