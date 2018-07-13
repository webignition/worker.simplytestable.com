<?php

namespace SimplyTestable\AppBundle\Controller;

use SimplyTestable\AppBundle\Resque\Job\TasksRequestJob;
use SimplyTestable\AppBundle\Services\Resque\QueueService;
use SimplyTestable\AppBundle\Services\TasksService;
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
