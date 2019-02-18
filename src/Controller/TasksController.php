<?php

namespace App\Controller;

use App\Resque\Job\TasksRequestJob;
use App\Services\Resque\QueueService;
use App\Services\TasksService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class TasksController extends AbstractController
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
