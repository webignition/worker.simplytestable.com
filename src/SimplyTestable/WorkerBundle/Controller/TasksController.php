<?php

namespace SimplyTestable\WorkerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class TasksController extends Controller
{
    public function notifyAction()
    {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        if ($resqueQueueService->isEmpty('tasks-request')) {
            $resqueQueueService->enqueue(
                $resqueJobFactory->create(
                    'tasks-request',
                    ['limit' => $this->container->getParameter('worker_process_count')]
                )
            );
        }

        return new Response();
    }
}
